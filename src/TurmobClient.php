<?php

namespace TurmobClient;

use Exception;
use DateTime;
use DateTimeZone;
use DOMDocument;

require_once 'CityMappings.php';

class TurmobClient {
    private $baseUrl = 'https://turmobefatura.luca.com.tr';
    private $cookie_file;
    private $logged_in = false;
    private $session_dir;

    public function __construct($session_id = null) {
        // Set up session directory in system temp
        $this->session_dir = sys_get_temp_dir() . '/turmob_sessions/';
        if (!file_exists($this->session_dir)) {
            mkdir($this->session_dir, 0777, true);
        }

        if ($session_id && $this->loadSession($session_id)) {
            $this->logged_in = true;
        } else {
            // Create a new temporary cookie file
            $this->cookie_file = tempnam($this->session_dir, 'TURMOB_');
        }
    }

    public function __destruct() {
        // Don't delete the cookie file if we're maintaining a session
        if (!$this->logged_in && file_exists($this->cookie_file)) {
            unlink($this->cookie_file);
        }
    }

    /**
     * Helper function to make cURL requests
     * 
     * @param string $url The URL to request
     * @param array $options Additional cURL options
     * @param bool $returnHttpCode Whether to return HTTP code
     * @return mixed Response body or array with body and http_code
     * @throws Exception On cURL error
     */
    private function request($url, $options = [], $returnHttpCode = false) {
        $ch = curl_init();
        
        // Set default options
        $defaultOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $this->cookie_file,
            CURLOPT_COOKIEJAR => $this->cookie_file,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        
        // Merge with user options (user options take precedence)
        curl_setopt_array($ch, $defaultOptions + $options);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Connection error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($returnHttpCode) {
            return [
                'body' => $response,
                'http_code' => $httpCode
            ];
        }
        
        return $response;
    }

    private function loadSession($session_id) {
        $session_file = $this->session_dir . 'TURMOB_' . preg_replace('/[^a-zA-Z0-9]/', '', $session_id);
        
        if (file_exists($session_file)) {
            // Check if session is not expired (24 hours)
            if (time() - filemtime($session_file) < 24 * 3600) {
                $this->cookie_file = $session_file;
                return true;
            } else {
                unlink($session_file); // Remove expired session
            }
        }
        return false;
    }

    public function saveSession() {
        if (!$this->logged_in) {
            throw new Exception('Cannot save session: Not logged in');
        }

        // Generate a unique session ID
        $session_id = bin2hex(random_bytes(16));
        $new_cookie_file = $this->session_dir . 'TURMOB_' . $session_id;

        // Copy current cookie file to session storage
        copy($this->cookie_file, $new_cookie_file);
        
        // Clean up the temporary cookie file if it's different
        if ($this->cookie_file !== $new_cookie_file && file_exists($this->cookie_file)) {
            unlink($this->cookie_file);
        }

        $this->cookie_file = $new_cookie_file;
        return $session_id;
    }

    public function validateSession() {
        if (!$this->logged_in) {
            return false;
        }

        $result = $this->request($this->baseUrl, [
            CURLOPT_HEADER => true
        ], true);

        // If we can access the home page, session is valid
        return $result['http_code'] === 200;
    }

    private function getRequestVerificationToken() {
        $html = $this->request($this->baseUrl . '/Account/Login');

        // Parse HTML to get the verification token
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $inputs = $dom->getElementsByTagName('input');
        
        foreach ($inputs as $input) {
            if ($input->getAttribute('name') === '__RequestVerificationToken') {
                return $input->getAttribute('value');
            }
        }
        
        throw new Exception('Could not find request verification token');
    }

    public function login($vknTckn, $password) {
        // First get the verification token
        $token = $this->getRequestVerificationToken();
        
        $postData = http_build_query([
            'VknTckn' => $vknTckn,
            'Password' => $password,
            'RememberMe' => 'on',
            '__RequestVerificationToken' => $token
        ]);

        $result = $this->request($this->baseUrl . '/Account/Login', [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => true
        ], true);

        // Check if login was successful by verifying the redirect
        if ($result['http_code'] == 302 || strpos($result['body'], $this->baseUrl) !== false) {
            $this->logged_in = true;
            return true;
        }

        return false;
    }

    public function isLoggedIn() {
        return $this->logged_in;
    }

    private function findRecipient($aliciAdi) {
        $query = http_build_query([
            'q' => $aliciAdi,
            'companyId' => 'undefined'
        ]);

        $response = $this->request($this->baseUrl . '/Invoice/GetRecipientList?' . $query);

        // Parse the JSON response
        $recipients = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from server');
        }

        // Handle different cases based on number of recipients found
        if (empty($recipients)) {
            return null; // No recipient found, can proceed with creation
        } else if (count($recipients) === 1) {
            return $recipients[0]['IdAlici']; // Return existing recipient ID
        } else {
            throw new Exception('Multiple recipients found with this name');
        }
    }

    private function getCountyId($cityName, $countyName) {
        // Get city ID from our mappings
        $cityId = CityMappings::getCityId($cityName);

        // Fetch counties for this city
        $response = $this->request($this->baseUrl . '/Invoice/GetCountyFromCityId?CityId=' . $cityId);

        // Parse the JSON response
        $counties = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from server');
        }

        // Find matching county
        $countyName = CityMappings::fixName($countyName);
        foreach ($counties as $county) {
            if (CityMappings::fixName($county['IlceAdi']) === $countyName) {
                return [
                    'cityId' => $cityId,
                    'countyId' => $county['IdIlce']
                ];
            }
        }

        throw new Exception("County not found: $countyName in city $cityName");
    }

    public function getInvoiceUser($aliciAdi, $vknTckn, $ilceAdi, $ilAdi) {
        if (!$this->logged_in) {
            throw new Exception('Must be logged in to get/create invoice user');
        }

        // Check if recipient exists and get ID if it does
        $existingId = $this->findRecipient($aliciAdi);
        if ($existingId !== null) {
            return $existingId; // Return existing recipient ID
        }

        // Get city and county IDs
        $location = $this->getCountyId($ilAdi, $ilceAdi);

        // Get the verification token from Invoice/Create page
        $html = $this->request($this->baseUrl . '/Invoice/Create');

        // Find the token value from the getAntiForgeryToken function
        if (preg_match('/let\s+token\s*=\s*\'<input[^>]*value="([^"]+)"[^>]*>\'/', $html, $matches)) {
            $token = $matches[1];
        } else {
            throw new Exception('Could not find antiforgery token in JavaScript');
        }

        // Prepare the POST data with required fields
        $postData = http_build_query([
            'AliciAdi' => $aliciAdi,
            'Vnktckn' => $vknTckn,
            'IdIlce' => $location['countyId'],
            'IdIl' => $location['cityId'],
            'IlAdi' => $ilAdi,
            // Set default values for required fields
            'WebSite' => '',
            'Telefon' => '',
            'FaturaGonderimSekli' => '2',
            'IdVergiDairesi' => '-1',
            'Fax' => '',
            'Email' => '',
            'SokakAdi' => '',
            'BinaNo' => '',
            'PostaKodu' => '',
            'AliciTipi' => '2',
            'IdAliciTipi' => '1',
            'IdFirma' => '180382',
            'Musterino' => '',
            'IrsaliyeAlicisi' => 'false',
            '__RequestVerificationToken' => $token
        ]);

        // Send the create request
        $response = $this->request($this->baseUrl . '/Recipient/Create', [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => false
        ]);

        // Decode JSON response
        $responseData = json_decode($response, true);
        
        // Check for error
        if (!empty($responseData['error'])) {
            throw new Exception($responseData['error']);
        }

        // Return the recipient ID
        return $responseData['IdAlici'];
    }

    private function getVerificationTokenFromCreateQuick() {
        $html = $this->request($this->baseUrl . '/Invoice/CreateQuick');
        
        // Find the token value from the getAntiForgeryToken function
        if (preg_match('/let\s+token\s*=\s*\'<input[^>]*value="([^"]+)"[^>]*>\'/', $html, $matches)) {
            return $matches[1];
        } else {
            throw new Exception('Could not find antiforgery token in JavaScript');
        }
    }

    public function createInvoice($idAlici, $products, $totalLineExtensionAmount, $totalVATAmount, $totalTaxInclusiveAmount, $totalDiscountAmount, $totalPayableAmount) {
        if (!$this->logged_in) {
            throw new Exception('Must be logged in to create an invoice');
        }

        // Get the verification token
        $token = $this->getVerificationTokenFromCreateQuick();

        // Prepare the jsonData
        $invoiceData = [
            "ETTN" => "",
            "InvoiceId" => "0",
            "RecipientType" => "2",
            "InvoiceNumber" => "",
            "CompanyId" => "180382",
            "ScenarioType" => "0",
            "ReceiverInboxTag" => null,
            "InvoiceDate" => date('d-m-Y'), // Current date
            "InvoiceTime" => (new DateTime('now', new DateTimeZone('Europe/Istanbul')))->format('H:i:s'), // Current time in Istanbul timezone
            "InvoiceType" => "1",
            "LastPaymentDate" => "",
            "DispatchList" => [],
            "IdAlici" => $idAlici,
            "Products" => array_map(function($product) {
                return [
                    "ProductInvoiceModelId" => 0,
                    "DiscountAmount" => $product['DiscountAmount'],
                    "DiscountRate" => $product['DiscountRate'],
                    "LineExtensionAmount" => $product['LineExtensionAmount'],
                    "MeasureUnitId" => 67, // Assuming a default value, adjust as necessary
                    "ProductId" => null, // Assuming no specific product ID
                    "ProductName" => $product['ProductName'],
                    "Quantity" => $product['Quantity'],
                    "TaxExemptionReason" => "",
                    "TaxExemptionReasonCode" => "",
                    "UnitPrice" => $product['UnitPrice'],
                    "VatAmount" => $product['VatAmount'],
                    "VatRate" => $product['VatRate'],
                    "AdditionalTaxes" => [],
                    "WitholdingTaxes" => [],
                    "Deleted" => false,
                    "DeliveryList" => [],
                    "CustomsTrackingList" => [],
                    "IdMensei" => 0,
                    "Mensei" => null,
                    "SiniflandirmaKodu" => null,
                    "IdSiniflandirmaKodu" => 0,
                    "GTipNoArcvh" => ""
                ];
            }, $products), // User provided products
            "CurrencyCode" => "TRY",
            "CrossRate" => 0,
            "TaxExemptionReason" => "",
            "Notes" => [""],
            "Receiver" => ["SendingType" => "2"],
            "IsFreeOfCharge" => false,
            "KismiIadeMi" => false,
            "CompanyBankAccountList" => [],
            "TotalLineExtensionAmount" => $totalLineExtensionAmount,
            "TotalVATAmount" => $totalVATAmount,
            "TotalTaxInclusiveAmount" => $totalTaxInclusiveAmount,
            "TotalDiscountAmount" => $totalDiscountAmount,
            "TotalPayableAmount" => $totalPayableAmount,
            "RoundCounter" => 0
        ];

        // Convert the invoice data to JSON
        $jsonData = json_encode($invoiceData);

        // Send the create invoice request
        $response = $this->request($this->baseUrl . '/Invoice/Create', [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['jsonData' => $jsonData, '__RequestVerificationToken' => $token]),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => false
        ]);

        // Trim and validate the invoice ID response
        $invoiceId = trim($response, " \t\n\r\0\x0B\"");
        if (strlen($invoiceId) !== 16) {
            return false;
        }
        return $invoiceId;
    }
} 