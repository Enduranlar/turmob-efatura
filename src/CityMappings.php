<?php

namespace TurmobClient;

use Exception;

class CityMappings {
    private static $cities = [
        'ADANA' => 1,
        'ADIYAMAN' => 12,
        'AFYONKARAHİSAR' => 23,
        'AĞRI' => 34,
        'AMASYA' => 45,
        'ANKARA' => 56,
        'ANTALYA' => 67,
        'ARTVİN' => 78,
        'AYDIN' => 81,
        'BALIKESİR' => 2,
        'BİLECİK' => 3,
        'BİNGÖL' => 4,
        'BİTLİS' => 5,
        'BOLU' => 6,
        'BURDUR' => 7,
        'BURSA' => 8,
        'ÇANAKKALE' => 9,
        'ÇANKIRI' => 10,
        'ÇORUM' => 11,
        'DENİZLİ' => 13,
        'DİYARBAKIR' => 14,
        'EDİRNE' => 15,
        'ELAZIĞ' => 16,
        'ERZİNCAN' => 17,
        'ERZURUM' => 18,
        'ESKİŞEHİR' => 19,
        'GAZİANTEP' => 20,
        'GİRESUN' => 21,
        'GÜMÜŞHANE' => 22,
        'HAKKARİ' => 24,
        'HATAY' => 25,
        'ISPARTA' => 26,
        'MERSİN' => 27,
        'İSTANBUL' => 28,
        'İZMİR' => 29,
        'KARS' => 30,
        'KASTAMONU' => 31,
        'KAYSERİ' => 32,
        'KIRKLARELİ' => 33,
        'KIRŞEHİR' => 35,
        'KOCAELİ' => 36,
        'KONYA' => 37,
        'KÜTAHYA' => 38,
        'MALATYA' => 39,
        'MANİSA' => 40,
        'KAHRAMANMARAŞ' => 41,
        'MARDİN' => 42,
        'MUĞLA' => 43,
        'MUŞ' => 44,
        'NEVŞEHİR' => 46,
        'NİĞDE' => 47,
        'ORDU' => 48,
        'RİZE' => 49,
        'SAKARYA' => 50,
        'SAMSUN' => 51,
        'SİİRT' => 52,
        'SİNOP' => 53,
        'SİVAS' => 54,
        'TEKİRDAĞ' => 55,
        'TOKAT' => 57,
        'TRABZON' => 58,
        'TUNCELİ' => 59,
        'ŞANLIURFA' => 60,
        'UŞAK' => 61,
        'VAN' => 62,
        'YOZGAT' => 63,
        'ZONGULDAK' => 64,
        'AKSARAY' => 65,
        'BAYBURT' => 66,
        'KARAMAN' => 68,
        'KIRIKKALE' => 69,
        'BATMAN' => 70,
        'ŞIRNAK' => 71,
        'BARTIN' => 72,
        'ARDAHAN' => 73,
        'IĞDIR' => 74,
        'YALOVA' => 75,
        'KARABÜK' => 76,
        'KİLİS' => 77,
        'OSMANİYE' => 79,
        'DÜZCE' => 80
    ];

    public static function getCityId($cityName) {
        $cityName = mb_strtoupper($cityName, 'UTF-8');
        if (isset(self::$cities[$cityName])) {
            return self::$cities[$cityName];
        }
        throw new Exception("City not found: $cityName");
    }
} 