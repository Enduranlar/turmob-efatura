<?php

namespace TurmobClient;

use Exception;

class CityMappings {
    private static $cities = [
        'ADANA' => 1,
        'ADIYAMAN' => 12,
        'AFYONKARAHISAR' => 23,
        'AGRI' => 34,
        'AMASYA' => 45,
        'ANKARA' => 56,
        'ANTALYA' => 67,
        'ARTVIN' => 78,
        'AYDIN' => 81,
        'BALIKESIR' => 2,
        'BILECIK' => 3,
        'BINGOL' => 4,
        'BITLIS' => 5,
        'BOLU' => 6,
        'BURDUR' => 7,
        'BURSA' => 8,
        'CANAKKALE' => 9,
        'CANKIRI' => 10,
        'CORUM' => 11,
        'DENIZLI' => 13,
        'DIYARBAKIR' => 14,
        'EDIRNE' => 15,
        'ELAZIG' => 16,
        'ERZINCAN' => 17,
        'ERZURUM' => 18,
        'ESKISEHIR' => 19,
        'GAZIANTEP' => 20,
        'GIRESUN' => 21,
        'GUMUSHANE' => 22,
        'HAKKARI' => 24,
        'HATAY' => 25,
        'ISPARTA' => 26,
        'MERSIN' => 27,
        'ISTANBUL' => 28,
        'IZMIR' => 29,
        'KARS' => 30,
        'KASTAMONU' => 31,
        'KAYSERI' => 32,
        'KIRKLARELI' => 33,
        'KIRSEHIR' => 35,
        'KOCAELI' => 36,
        'KONYA' => 37,
        'KUTAHYA' => 38,
        'MALATYA' => 39,
        'MANISA' => 40,
        'KAHRAMANMARAS' => 41,
        'MARDIN' => 42,
        'MUGLA' => 43,
        'MUS' => 44,
        'NEVSEHIR' => 46,
        'NIGDE' => 47,
        'ORDU' => 48,
        'RIZE' => 49,
        'SAKARYA' => 50,
        'SAMSUN' => 51,
        'SIIRT' => 52,
        'SINOP' => 53,
        'SIVAS' => 54,
        'TEKIRDAG' => 55,
        'TOKAT' => 57,
        'TRABZON' => 58,
        'TUNCELI' => 59,
        'SANLIURFA' => 60,
        'USAK' => 61,
        'VAN' => 62,
        'YOZGAT' => 63,
        'ZONGULDAK' => 64,
        'AKSARAY' => 65,
        'BAYBURT' => 66,
        'KARAMAN' => 68,
        'KIRIKKALE' => 69,
        'BATMAN' => 70,
        'SIRNAK' => 71,
        'BARTIN' => 72,
        'ARDAHAN' => 73,
        'IGDIR' => 74,
        'YALOVA' => 75,
        'KARABUK' => 76,
        'KILIS' => 77,
        'OSMANIYE' => 79,
        'DUZCE' => 80
    ];

    public static function getCityId($cityName) {
        $cityName = self::fixName($cityName);

        if (isset(self::$cities[$cityName])) {
            return self::$cities[$cityName];
        }
        throw new Exception("City not found: $cityName");
    }

    public static function fixName($name) {
        $name = mb_strtoupper($name, 'UTF-8');
        $name = str_replace(
            ['İ', 'Ü', 'Ö', 'Ş', 'Ğ', 'Ç'],
            ['I', 'U', 'O', 'S', 'G', 'C'],
            $name
        );
        return $name;
    }
}