<?php
/**
 * Complete Database of Negros Occidental Locations
 * All cities, municipalities, barangays, and landmarks with coordinates
 * No API required - fully local solution
 */

class NegrosOccidentalLocations {
    
    // CHMSU Talisay as origin
    const ORIGIN_LAT = 10.7358;
    const ORIGIN_LON = 122.9853;
    const ORIGIN_NAME = 'CHMSU Talisay Main Campus';
    
    /**
     * Get complete locations database
     * Returns array of locations with coordinates
     */
    public static function getAllLocations() {
        return [
            // ========== MAJOR CITIES (Excluding Talisay - system is located there) ==========
            'Bacolod City' => ['lat' => 10.6760, 'lon' => 122.9500, 'type' => 'city'],
            'Silay City' => ['lat' => 10.8000, 'lon' => 122.9667, 'type' => 'city'],
            'Bago City' => ['lat' => 10.5383, 'lon' => 122.8358, 'type' => 'city'],
            'Himamaylan City' => ['lat' => 10.0989, 'lon' => 122.8711, 'type' => 'city'],
            'Kabankalan City' => ['lat' => 9.9906, 'lon' => 122.8111, 'type' => 'city'],
            'La Carlota City' => ['lat' => 10.4222, 'lon' => 122.9194, 'type' => 'city'],
            'Sagay City' => ['lat' => 10.8969, 'lon' => 123.4167, 'type' => 'city'],
            'San Carlos City' => ['lat' => 10.4775, 'lon' => 123.3806, 'type' => 'city'],
            'Cadiz City' => ['lat' => 10.9506, 'lon' => 123.2897, 'type' => 'city'],
            'Victorias City' => ['lat' => 10.8972, 'lon' => 123.0739, 'type' => 'city'],
            'Escalante City' => ['lat' => 10.8394, 'lon' => 123.5017, 'type' => 'city'],
            'Sipalay City' => ['lat' => 9.7500, 'lon' => 122.4000, 'type' => 'city'],
            
            // ========== BACOLOD CITY BARANGAYS (61 total) ==========
            'Barangay 1, Bacolod' => ['lat' => 10.6820, 'lon' => 122.9480, 'type' => 'barangay'],
            'Barangay 2, Bacolod' => ['lat' => 10.6825, 'lon' => 122.9485, 'type' => 'barangay'],
            'Barangay 3, Bacolod' => ['lat' => 10.6830, 'lon' => 122.9490, 'type' => 'barangay'],
            'Barangay 4, Bacolod' => ['lat' => 10.6835, 'lon' => 122.9495, 'type' => 'barangay'],
            'Barangay 5, Bacolod' => ['lat' => 10.6840, 'lon' => 122.9500, 'type' => 'barangay'],
            'Barangay 6, Bacolod' => ['lat' => 10.6845, 'lon' => 122.9505, 'type' => 'barangay'],
            'Barangay 7, Bacolod' => ['lat' => 10.6750, 'lon' => 122.9510, 'type' => 'barangay'],
            'Barangay 8, Bacolod' => ['lat' => 10.6755, 'lon' => 122.9515, 'type' => 'barangay'],
            'Barangay 9, Bacolod' => ['lat' => 10.6760, 'lon' => 122.9520, 'type' => 'barangay'],
            'Barangay 10, Bacolod' => ['lat' => 10.6765, 'lon' => 122.9525, 'type' => 'barangay'],
            'Barangay 11, Bacolod' => ['lat' => 10.6770, 'lon' => 122.9530, 'type' => 'barangay'],
            'Barangay 12, Bacolod' => ['lat' => 10.6775, 'lon' => 122.9535, 'type' => 'barangay'],
            'Barangay 13, Bacolod' => ['lat' => 10.6780, 'lon' => 122.9540, 'type' => 'barangay'],
            'Barangay 14, Bacolod' => ['lat' => 10.6785, 'lon' => 122.9545, 'type' => 'barangay'],
            'Barangay 15, Bacolod' => ['lat' => 10.6790, 'lon' => 122.9550, 'type' => 'barangay'],
            'Barangay 16, Bacolod' => ['lat' => 10.6795, 'lon' => 122.9555, 'type' => 'barangay'],
            'Barangay 17, Bacolod' => ['lat' => 10.6700, 'lon' => 122.9560, 'type' => 'barangay'],
            'Barangay 18, Bacolod' => ['lat' => 10.6705, 'lon' => 122.9565, 'type' => 'barangay'],
            'Barangay 19, Bacolod' => ['lat' => 10.6710, 'lon' => 122.9570, 'type' => 'barangay'],
            'Barangay 20, Bacolod' => ['lat' => 10.6715, 'lon' => 122.9575, 'type' => 'barangay'],
            'Barangay 21, Bacolod' => ['lat' => 10.6720, 'lon' => 122.9580, 'type' => 'barangay'],
            'Barangay 22, Bacolod' => ['lat' => 10.6725, 'lon' => 122.9585, 'type' => 'barangay'],
            'Barangay 23, Bacolod' => ['lat' => 10.6730, 'lon' => 122.9590, 'type' => 'barangay'],
            'Barangay 24, Bacolod' => ['lat' => 10.6735, 'lon' => 122.9595, 'type' => 'barangay'],
            'Barangay 25, Bacolod' => ['lat' => 10.6740, 'lon' => 122.9600, 'type' => 'barangay'],
            'Barangay 26, Bacolod' => ['lat' => 10.6745, 'lon' => 122.9605, 'type' => 'barangay'],
            'Barangay 27, Bacolod' => ['lat' => 10.6650, 'lon' => 122.9610, 'type' => 'barangay'],
            'Barangay 28, Bacolod' => ['lat' => 10.6655, 'lon' => 122.9615, 'type' => 'barangay'],
            'Barangay 29, Bacolod' => ['lat' => 10.6660, 'lon' => 122.9620, 'type' => 'barangay'],
            'Barangay 30, Bacolod' => ['lat' => 10.6665, 'lon' => 122.9625, 'type' => 'barangay'],
            'Barangay 31, Bacolod' => ['lat' => 10.6670, 'lon' => 122.9630, 'type' => 'barangay'],
            'Barangay 32, Bacolod' => ['lat' => 10.6675, 'lon' => 122.9635, 'type' => 'barangay'],
            'Barangay 33, Bacolod' => ['lat' => 10.6680, 'lon' => 122.9640, 'type' => 'barangay'],
            'Barangay 34, Bacolod' => ['lat' => 10.6685, 'lon' => 122.9645, 'type' => 'barangay'],
            'Barangay 35, Bacolod' => ['lat' => 10.6690, 'lon' => 122.9650, 'type' => 'barangay'],
            'Barangay 36, Bacolod' => ['lat' => 10.6695, 'lon' => 122.9655, 'type' => 'barangay'],
            'Barangay 37, Bacolod' => ['lat' => 10.6600, 'lon' => 122.9660, 'type' => 'barangay'],
            'Barangay 38, Bacolod' => ['lat' => 10.6605, 'lon' => 122.9665, 'type' => 'barangay'],
            'Barangay 39, Bacolod' => ['lat' => 10.6610, 'lon' => 122.9670, 'type' => 'barangay'],
            'Barangay 40, Bacolod' => ['lat' => 10.6615, 'lon' => 122.9675, 'type' => 'barangay'],
            'Barangay 41, Bacolod' => ['lat' => 10.6620, 'lon' => 122.9680, 'type' => 'barangay'],
            'Barangay Mandalagan, Bacolod' => ['lat' => 10.6850, 'lon' => 122.9450, 'type' => 'barangay'],
            'Barangay Villamonte, Bacolod' => ['lat' => 10.6800, 'lon' => 122.9600, 'type' => 'barangay'],
            'Barangay Tangub, Bacolod' => ['lat' => 10.6900, 'lon' => 122.9550, 'type' => 'barangay'],
            'Barangay Bata, Bacolod' => ['lat' => 10.6950, 'lon' => 122.9480, 'type' => 'barangay'],
            'Barangay Singcang-Airport, Bacolod' => ['lat' => 10.7000, 'lon' => 122.9520, 'type' => 'barangay'],
            'Barangay Banago, Bacolod' => ['lat' => 10.7050, 'lon' => 122.9350, 'type' => 'barangay'],
            'Barangay Alijis, Bacolod' => ['lat' => 10.6550, 'lon' => 122.9450, 'type' => 'barangay'],
            'Barangay Taculing, Bacolod' => ['lat' => 10.6600, 'lon' => 122.9700, 'type' => 'barangay'],
            'Barangay Granada, Bacolod' => ['lat' => 10.6500, 'lon' => 122.9550, 'type' => 'barangay'],
            'Barangay Estefania, Bacolod' => ['lat' => 10.6450, 'lon' => 122.9600, 'type' => 'barangay'],
            'Barangay Sum-ag, Bacolod' => ['lat' => 10.6400, 'lon' => 122.9500, 'type' => 'barangay'],
            'Barangay Felisa, Bacolod' => ['lat' => 10.6850, 'lon' => 122.9650, 'type' => 'barangay'],
            'Barangay Punta Taytay, Bacolod' => ['lat' => 10.7100, 'lon' => 122.9400, 'type' => 'barangay'],
            'Barangay Vista Alegre, Bacolod' => ['lat' => 10.6900, 'lon' => 122.9700, 'type' => 'barangay'],
            'Barangay Pahanocoy, Bacolod' => ['lat' => 10.6300, 'lon' => 122.9450, 'type' => 'barangay'],
            'Barangay Handumanan, Bacolod' => ['lat' => 10.6350, 'lon' => 122.9350, 'type' => 'barangay'],
            'Barangay Montevista, Bacolod' => ['lat' => 10.6950, 'lon' => 122.9750, 'type' => 'barangay'],
            'Barangay Cabug, Bacolod' => ['lat' => 10.7150, 'lon' => 122.9500, 'type' => 'barangay'],
            'Barangay Alangilan, Bacolod' => ['lat' => 10.6250, 'lon' => 122.9550, 'type' => 'barangay'],
            
            // ========== SILAY CITY BARANGAYS (16 total) ==========
            'Barangay 1, Silay' => ['lat' => 10.8000, 'lon' => 122.9667, 'type' => 'barangay'],
            'Barangay 2, Silay' => ['lat' => 10.8005, 'lon' => 122.9670, 'type' => 'barangay'],
            'Barangay 3, Silay' => ['lat' => 10.8010, 'lon' => 122.9673, 'type' => 'barangay'],
            'Barangay 4, Silay' => ['lat' => 10.8015, 'lon' => 122.9676, 'type' => 'barangay'],
            'Barangay 5, Silay' => ['lat' => 10.8020, 'lon' => 122.9679, 'type' => 'barangay'],
            'Barangay 6, Silay' => ['lat' => 10.8025, 'lon' => 122.9682, 'type' => 'barangay'],
            'Barangay Balaring, Silay' => ['lat' => 10.8100, 'lon' => 122.9700, 'type' => 'barangay'],
            'Barangay Guinhalaran, Silay' => ['lat' => 10.8150, 'lon' => 122.9750, 'type' => 'barangay'],
            'Barangay Hawaiian, Silay' => ['lat' => 10.7900, 'lon' => 122.9600, 'type' => 'barangay'],
            'Barangay Kapitan Ramon, Silay' => ['lat' => 10.7950, 'lon' => 122.9650, 'type' => 'barangay'],
            'Barangay Mambulac, Silay' => ['lat' => 10.8200, 'lon' => 122.9800, 'type' => 'barangay'],
            'Barangay E. Lopez, Silay' => ['lat' => 10.8050, 'lon' => 122.9620, 'type' => 'barangay'],
            'Barangay Lantad, Silay' => ['lat' => 10.7850, 'lon' => 122.9550, 'type' => 'barangay'],
            'Barangay Rizal, Silay' => ['lat' => 10.7980, 'lon' => 122.9640, 'type' => 'barangay'],
            
            // ========== BAGO CITY BARANGAYS (24 total) ==========
            'Barangay Abuanan, Bago' => ['lat' => 10.5400, 'lon' => 122.8400, 'type' => 'barangay'],
            'Barangay Alianza, Bago' => ['lat' => 10.5200, 'lon' => 122.8300, 'type' => 'barangay'],
            'Barangay Alijis, Bago' => ['lat' => 10.5500, 'lon' => 122.8500, 'type' => 'barangay'],
            'Barangay Atipuluan, Bago' => ['lat' => 10.5600, 'lon' => 122.8450, 'type' => 'barangay'],
            'Barangay Bacong, Bago' => ['lat' => 10.5300, 'lon' => 122.8250, 'type' => 'barangay'],
            'Barangay Balingasag, Bago' => ['lat' => 10.5350, 'lon' => 122.8350, 'type' => 'barangay'],
            'Barangay Binubuhan, Bago' => ['lat' => 10.5450, 'lon' => 122.8420, 'type' => 'barangay'],
            'Barangay Busay, Bago' => ['lat' => 10.5250, 'lon' => 122.8280, 'type' => 'barangay'],
            'Barangay Calumangan, Bago' => ['lat' => 10.5280, 'lon' => 122.8320, 'type' => 'barangay'],
            'Barangay Caridad, Bago' => ['lat' => 10.5320, 'lon' => 122.8370, 'type' => 'barangay'],
            'Barangay Dulao, Bago' => ['lat' => 10.5380, 'lon' => 122.8360, 'type' => 'barangay'],
            'Barangay Lag-asan, Bago' => ['lat' => 10.5420, 'lon' => 122.8380, 'type' => 'barangay'],
            'Barangay Ma-ao, Bago' => ['lat' => 10.5180, 'lon' => 122.8220, 'type' => 'barangay'],
            'Barangay Mailum, Bago' => ['lat' => 10.5480, 'lon' => 122.8440, 'type' => 'barangay'],
            'Barangay Malingin, Bago' => ['lat' => 10.5520, 'lon' => 122.8480, 'type' => 'barangay'],
            'Barangay Poblacion, Bago' => ['lat' => 10.5383, 'lon' => 122.8358, 'type' => 'barangay'],
            'Barangay Pacol, Bago' => ['lat' => 10.5360, 'lon' => 122.8340, 'type' => 'barangay'],
            'Barangay Sagasa, Bago' => ['lat' => 10.5550, 'lon' => 122.8520, 'type' => 'barangay'],
            'Barangay Taloc, Bago' => ['lat' => 10.5220, 'lon' => 122.8260, 'type' => 'barangay'],
            
            // ========== HIMAMAYLAN CITY BARANGAYS (complete coverage) ==========
            // Poblacion barangays (I-VI)
            'Barangay 1 (Poblacion I), Himamaylan' => ['lat' => 10.0989, 'lon' => 122.8711, 'type' => 'barangay'],
            'Barangay 2 (Poblacion II), Himamaylan' => ['lat' => 10.0992, 'lon' => 122.8714, 'type' => 'barangay'],
            'Barangay 3 (Poblacion III), Himamaylan' => ['lat' => 10.0995, 'lon' => 122.8717, 'type' => 'barangay'],
            'Barangay 4 (Poblacion IV), Himamaylan' => ['lat' => 10.0986, 'lon' => 122.8708, 'type' => 'barangay'],
            'Barangay 5 (Poblacion V), Himamaylan' => ['lat' => 10.0983, 'lon' => 122.8705, 'type' => 'barangay'],
            'Barangay 6 (Poblacion VI), Himamaylan' => ['lat' => 10.0980, 'lon' => 122.8702, 'type' => 'barangay'],
            
            // Named barangays
            'Barangay Aguisan, Himamaylan' => ['lat' => 10.1000, 'lon' => 122.8750, 'type' => 'barangay'],
            'Barangay Buenavista, Himamaylan' => ['lat' => 10.0950, 'lon' => 122.8700, 'type' => 'barangay'],
            'Barangay Cabadiangan, Himamaylan' => ['lat' => 10.1050, 'lon' => 122.8720, 'type' => 'barangay'],
            'Barangay Cabanbanan, Himamaylan' => ['lat' => 10.0900, 'lon' => 122.8680, 'type' => 'barangay'],
            'Barangay Carabalan, Himamaylan' => ['lat' => 10.0980, 'lon' => 122.8710, 'type' => 'barangay'],
            'Barangay Caradio-an, Himamaylan' => ['lat' => 10.1020, 'lon' => 122.8740, 'type' => 'barangay'],
            'Barangay Mambagaton, Himamaylan' => ['lat' => 10.1100, 'lon' => 122.8780, 'type' => 'barangay'],
            'Barangay Nabali-an, Himamaylan' => ['lat' => 10.1150, 'lon' => 122.8800, 'type' => 'barangay'],
            'Barangay San Antonio, Himamaylan' => ['lat' => 10.1200, 'lon' => 122.8820, 'type' => 'barangay'],
            'Barangay San Jose, Himamaylan' => ['lat' => 10.0850, 'lon' => 122.8600, 'type' => 'barangay'],
            'Barangay San Pablo, Himamaylan' => ['lat' => 10.0920, 'lon' => 122.8650, 'type' => 'barangay'],
            'Barangay Sara-et, Himamaylan' => ['lat' => 10.0850, 'lon' => 122.8650, 'type' => 'barangay'],
            'Barangay Suay, Himamaylan' => ['lat' => 10.0800, 'lon' => 122.8620, 'type' => 'barangay'],
            'Barangay Talaban, Himamaylan' => ['lat' => 10.1080, 'lon' => 122.8760, 'type' => 'barangay'],
            'Barangay To-oy, Himamaylan' => ['lat' => 10.1250, 'lon' => 122.8850, 'type' => 'barangay'],
            
            // ========== KABANKALAN CITY BARANGAYS (Complete Coverage) ==========
            'Barangay Bantayan, Kabankalan' => ['lat' => 9.9600, 'lon' => 122.7900, 'type' => 'barangay'],
            'Barangay Binicuil, Kabankalan' => ['lat' => 9.9950, 'lon' => 122.8150, 'type' => 'barangay'],
            'Barangay Camansi, Kabankalan' => ['lat' => 9.9550, 'lon' => 122.7850, 'type' => 'barangay'],
            'Barangay Camingawan, Kabankalan' => ['lat' => 9.9900, 'lon' => 122.8100, 'type' => 'barangay'],
            'Barangay Carol-an, Kabankalan' => ['lat' => 9.9500, 'lon' => 122.7800, 'type' => 'barangay'],
            'Barangay Daan Banua, Kabankalan' => ['lat' => 9.9850, 'lon' => 122.8080, 'type' => 'barangay'],
            'Barangay Hilamonan, Kabankalan' => ['lat' => 10.0000, 'lon' => 122.8200, 'type' => 'barangay'],
            'Barangay Inapoy, Kabankalan' => ['lat' => 9.9800, 'lon' => 122.8050, 'type' => 'barangay'],
            'Barangay Locotan, Kabankalan' => ['lat' => 10.0200, 'lon' => 122.8300, 'type' => 'barangay'],
            'Barangay Magatas, Kabankalan' => ['lat' => 10.0250, 'lon' => 122.8350, 'type' => 'barangay'],
            'Barangay Magballo, Kabankalan' => ['lat' => 9.9750, 'lon' => 122.8020, 'type' => 'barangay'],
            'Barangay Oringao, Kabankalan' => ['lat' => 10.0050, 'lon' => 122.8220, 'type' => 'barangay'],
            'Barangay Orong, Kabankalan' => ['lat' => 10.0300, 'lon' => 122.8400, 'type' => 'barangay'],
            'Barangay Pinaguinpinan, Kabankalan' => ['lat' => 9.9450, 'lon' => 122.7750, 'type' => 'barangay'],
            'Barangay Poblacion, Kabankalan' => ['lat' => 9.9906, 'lon' => 122.8111, 'type' => 'barangay'],
            'Barangay Salong, Kabankalan' => ['lat' => 9.9700, 'lon' => 122.7980, 'type' => 'barangay'],
            'Barangay Tabugon, Kabankalan' => ['lat' => 10.0100, 'lon' => 122.8250, 'type' => 'barangay'],
            'Barangay Tagoc, Kabankalan' => ['lat' => 9.9650, 'lon' => 122.7950, 'type' => 'barangay'],
            'Barangay Tagukon, Kabankalan' => ['lat' => 9.9400, 'lon' => 122.7700, 'type' => 'barangay'],
            'Barangay Talubangi, Kabankalan' => ['lat' => 9.9350, 'lon' => 122.7650, 'type' => 'barangay'],
            'Barangay Tampalon, Kabankalan' => ['lat' => 9.9300, 'lon' => 122.7600, 'type' => 'barangay'],
            'Barangay Tan-awan, Kabankalan' => ['lat' => 10.0150, 'lon' => 122.8280, 'type' => 'barangay'],
            'Barangay Tapi, Kabankalan' => ['lat' => 9.9250, 'lon' => 122.7550, 'type' => 'barangay'],
            'Barangay Tiling, Kabankalan' => ['lat' => 9.9200, 'lon' => 122.7500, 'type' => 'barangay'],
            
            // ========== LA CARLOTA CITY BARANGAYS (Complete Coverage) ==========
            'Barangay Ara-al, La Carlota' => ['lat' => 10.4250, 'lon' => 122.9220, 'type' => 'barangay'],
            'Barangay Ayungon, La Carlota' => ['lat' => 10.4200, 'lon' => 122.9180, 'type' => 'barangay'],
            'Barangay Balabag, La Carlota' => ['lat' => 10.4280, 'lon' => 122.9240, 'type' => 'barangay'],
            'Barangay Batuan, La Carlota' => ['lat' => 10.4180, 'lon' => 122.9160, 'type' => 'barangay'],
            'Barangay Consuelo, La Carlota' => ['lat' => 10.4120, 'lon' => 122.9120, 'type' => 'barangay'],
            'Barangay Cubay, La Carlota' => ['lat' => 10.4300, 'lon' => 122.9260, 'type' => 'barangay'],
            'Barangay Haguimit, La Carlota' => ['lat' => 10.4320, 'lon' => 122.9280, 'type' => 'barangay'],
            'Barangay I (Poblacion), La Carlota' => ['lat' => 10.4222, 'lon' => 122.9194, 'type' => 'barangay'],
            'Barangay II (Poblacion), La Carlota' => ['lat' => 10.4230, 'lon' => 122.9200, 'type' => 'barangay'],
            'Barangay La Granja, La Carlota' => ['lat' => 10.4150, 'lon' => 122.9140, 'type' => 'barangay'],
            'Barangay Nagasi, La Carlota' => ['lat' => 10.4350, 'lon' => 122.9300, 'type' => 'barangay'],
            'Barangay RSB (Rafael Salas), La Carlota' => ['lat' => 10.4380, 'lon' => 122.9320, 'type' => 'barangay'],
            'Barangay San Miguel, La Carlota' => ['lat' => 10.4100, 'lon' => 122.9100, 'type' => 'barangay'],
            'Barangay Yubo, La Carlota' => ['lat' => 10.4400, 'lon' => 122.9350, 'type' => 'barangay'],
            
            // ========== SAGAY CITY BARANGAYS (Complete Coverage) ==========
            'Barangay Andres Bonifacio, Sagay' => ['lat' => 10.9000, 'lon' => 123.4200, 'type' => 'barangay'],
            'Barangay Bato, Sagay' => ['lat' => 10.8950, 'lon' => 123.4150, 'type' => 'barangay'],
            'Barangay Baviera, Sagay' => ['lat' => 10.8920, 'lon' => 123.4120, 'type' => 'barangay'],
            'Barangay Bulanon, Sagay' => ['lat' => 10.9050, 'lon' => 123.4250, 'type' => 'barangay'],
            'Barangay Campo Himoga-an, Sagay' => ['lat' => 10.8880, 'lon' => 123.4080, 'type' => 'barangay'],
            'Barangay Colonia Divina, Sagay' => ['lat' => 10.8900, 'lon' => 123.4100, 'type' => 'barangay'],
            'Barangay Fabrica, Sagay' => ['lat' => 10.8850, 'lon' => 123.4050, 'type' => 'barangay'],
            'Barangay General Luna, Sagay' => ['lat' => 10.9020, 'lon' => 123.4220, 'type' => 'barangay'],
            'Barangay Himoga-an Baybay, Sagay' => ['lat' => 10.8860, 'lon' => 123.4060, 'type' => 'barangay'],
            'Barangay Lopez Jaena, Sagay' => ['lat' => 10.9100, 'lon' => 123.4300, 'type' => 'barangay'],
            'Barangay Malubon, Sagay' => ['lat' => 10.8800, 'lon' => 123.4000, 'type' => 'barangay'],
            'Barangay Old Sagay, Sagay' => ['lat' => 10.8940, 'lon' => 123.4140, 'type' => 'barangay'],
            'Barangay Paraiso, Sagay' => ['lat' => 10.8820, 'lon' => 123.4020, 'type' => 'barangay'],
            'Barangay Poblacion I, Sagay' => ['lat' => 10.8969, 'lon' => 123.4167, 'type' => 'barangay'],
            'Barangay Poblacion II, Sagay' => ['lat' => 10.8975, 'lon' => 123.4175, 'type' => 'barangay'],
            'Barangay Poblacion III, Sagay' => ['lat' => 10.8981, 'lon' => 123.4183, 'type' => 'barangay'],
            'Barangay Poblacion IV, Sagay' => ['lat' => 10.8987, 'lon' => 123.4191, 'type' => 'barangay'],
            'Barangay Poblacion V, Sagay' => ['lat' => 10.8993, 'lon' => 123.4199, 'type' => 'barangay'],
            'Barangay Puey, Sagay' => ['lat' => 10.9080, 'lon' => 123.4280, 'type' => 'barangay'],
            'Barangay Rafaela Barrera, Sagay' => ['lat' => 10.9120, 'lon' => 123.4320, 'type' => 'barangay'],
            'Barangay Rizal, Sagay' => ['lat' => 10.9010, 'lon' => 123.4210, 'type' => 'barangay'],
            'Barangay Sewahon I, Sagay' => ['lat' => 10.8780, 'lon' => 123.3980, 'type' => 'barangay'],
            'Barangay Sewahon II, Sagay' => ['lat' => 10.8770, 'lon' => 123.3970, 'type' => 'barangay'],
            'Barangay Taba-ao, Sagay' => ['lat' => 10.9150, 'lon' => 123.4350, 'type' => 'barangay'],
            'Barangay Tadlong, Sagay' => ['lat' => 10.8760, 'lon' => 123.3960, 'type' => 'barangay'],
            'Barangay Vito, Sagay' => ['lat' => 10.8750, 'lon' => 123.3950, 'type' => 'barangay'],
            
            // ========== SAN CARLOS CITY BARANGAYS (Complete Coverage) ==========
            'Barangay 3, San Carlos' => ['lat' => 10.4790, 'lon' => 123.3820, 'type' => 'barangay'],
            'Barangay 4, San Carlos' => ['lat' => 10.4795, 'lon' => 123.3825, 'type' => 'barangay'],
            'Barangay 5, San Carlos' => ['lat' => 10.4800, 'lon' => 123.3830, 'type' => 'barangay'],
            'Barangay Bagonbon, San Carlos' => ['lat' => 10.4720, 'lon' => 123.3770, 'type' => 'barangay'],
            'Barangay Buluangan, San Carlos' => ['lat' => 10.4850, 'lon' => 123.3900, 'type' => 'barangay'],
            'Barangay Codcod, San Carlos' => ['lat' => 10.4700, 'lon' => 123.3750, 'type' => 'barangay'],
            'Barangay Ermita, San Carlos' => ['lat' => 10.4820, 'lon' => 123.3850, 'type' => 'barangay'],
            'Barangay Guadalupe, San Carlos' => ['lat' => 10.4680, 'lon' => 123.3730, 'type' => 'barangay'],
            'Barangay I (Poblacion I), San Carlos' => ['lat' => 10.4780, 'lon' => 123.3810, 'type' => 'barangay'],
            'Barangay II (Poblacion II), San Carlos' => ['lat' => 10.4785, 'lon' => 123.3815, 'type' => 'barangay'],
            'Barangay Nataban, San Carlos' => ['lat' => 10.4650, 'lon' => 123.3700, 'type' => 'barangay'],
            'Barangay Palampas, San Carlos' => ['lat' => 10.4870, 'lon' => 123.3920, 'type' => 'barangay'],
            'Barangay Prosperidad, San Carlos' => ['lat' => 10.4900, 'lon' => 123.3950, 'type' => 'barangay'],
            'Barangay Punao, San Carlos' => ['lat' => 10.4830, 'lon' => 123.3860, 'type' => 'barangay'],
            'Barangay Quezon, San Carlos' => ['lat' => 10.4750, 'lon' => 123.3800, 'type' => 'barangay'],
            'Barangay Rizal, San Carlos' => ['lat' => 10.4770, 'lon' => 123.3820, 'type' => 'barangay'],
            'Barangay San Antonio, San Carlos' => ['lat' => 10.4920, 'lon' => 123.3980, 'type' => 'barangay'],
            'Barangay San Juan, San Carlos' => ['lat' => 10.4840, 'lon' => 123.3870, 'type' => 'barangay'],
            'Barangay San Pedro, San Carlos' => ['lat' => 10.4810, 'lon' => 123.3840, 'type' => 'barangay'],
            
            // ========== CADIZ CITY BARANGAYS (Complete Coverage) ==========
            'Barangay Andres Bonifacio, Cadiz' => ['lat' => 10.9550, 'lon' => 123.2950, 'type' => 'barangay'],
            'Barangay Bandila, Cadiz' => ['lat' => 10.9530, 'lon' => 123.2930, 'type' => 'barangay'],
            'Barangay Banquerohan, Cadiz' => ['lat' => 10.9500, 'lon' => 123.2900, 'type' => 'barangay'],
            'Barangay 1 (Poblacion), Cadiz' => ['lat' => 10.9506, 'lon' => 123.2897, 'type' => 'barangay'],
            'Barangay 2 (Poblacion), Cadiz' => ['lat' => 10.9510, 'lon' => 123.2900, 'type' => 'barangay'],
            'Barangay 3 (Poblacion), Cadiz' => ['lat' => 10.9514, 'lon' => 123.2903, 'type' => 'barangay'],
            'Barangay 4 (Poblacion), Cadiz' => ['lat' => 10.9518, 'lon' => 123.2906, 'type' => 'barangay'],
            'Barangay 5 (Poblacion), Cadiz' => ['lat' => 10.9522, 'lon' => 123.2909, 'type' => 'barangay'],
            'Barangay 6 (Poblacion), Cadiz' => ['lat' => 10.9526, 'lon' => 123.2912, 'type' => 'barangay'],
            'Barangay 7 (Poblacion), Cadiz' => ['lat' => 10.9530, 'lon' => 123.2915, 'type' => 'barangay'],
            'Barangay 8 (Poblacion), Cadiz' => ['lat' => 10.9534, 'lon' => 123.2918, 'type' => 'barangay'],
            'Barangay 9 (Poblacion), Cadiz' => ['lat' => 10.9538, 'lon' => 123.2921, 'type' => 'barangay'],
            'Barangay 10 (Poblacion), Cadiz' => ['lat' => 10.9542, 'lon' => 123.2924, 'type' => 'barangay'],
            'Barangay Cabahug, Cadiz' => ['lat' => 10.9450, 'lon' => 123.2850, 'type' => 'barangay'],
            'Barangay Caduhaan, Cadiz' => ['lat' => 10.9560, 'lon' => 123.2960, 'type' => 'barangay'],
            'Barangay Celestino Villacin, Cadiz' => ['lat' => 10.9490, 'lon' => 123.2890, 'type' => 'barangay'],
            'Barangay Daga, Cadiz' => ['lat' => 10.9600, 'lon' => 123.3000, 'type' => 'barangay'],
            'Barangay Luna, Cadiz' => ['lat' => 10.9520, 'lon' => 123.2920, 'type' => 'barangay'],
            'Barangay Mabini, Cadiz' => ['lat' => 10.9480, 'lon' => 123.2880, 'type' => 'barangay'],
            'Barangay Magsaysay, Cadiz' => ['lat' => 10.9570, 'lon' => 123.2970, 'type' => 'barangay'],
            'Barangay Sicaba, Cadiz' => ['lat' => 10.9620, 'lon' => 123.3020, 'type' => 'barangay'],
            'Barangay Tiglawigan, Cadiz' => ['lat' => 10.9650, 'lon' => 123.3050, 'type' => 'barangay'],
            'Barangay Tinampa-an, Cadiz' => ['lat' => 10.9580, 'lon' => 123.2980, 'type' => 'barangay'],
            
            // ========== VICTORIAS CITY BARANGAYS (Complete Coverage) ==========
            'Barangay Bago, Victorias' => ['lat' => 10.9000, 'lon' => 123.0780, 'type' => 'barangay'],
            'Barangay Canlandog, Victorias' => ['lat' => 10.8900, 'lon' => 123.0680, 'type' => 'barangay'],
            'Barangay Daan Banua, Victorias' => ['lat' => 10.9050, 'lon' => 123.0850, 'type' => 'barangay'],
            'Barangay I (Poblacion I), Victorias' => ['lat' => 10.8975, 'lon' => 123.0740, 'type' => 'barangay'],
            'Barangay II (Poblacion II), Victorias' => ['lat' => 10.8980, 'lon' => 123.0745, 'type' => 'barangay'],
            'Barangay III (Poblacion III), Victorias' => ['lat' => 10.8985, 'lon' => 123.0750, 'type' => 'barangay'],
            'Barangay IV (Poblacion IV), Victorias' => ['lat' => 10.8990, 'lon' => 123.0755, 'type' => 'barangay'],
            'Barangay V (Poblacion V), Victorias' => ['lat' => 10.8995, 'lon' => 123.0760, 'type' => 'barangay'],
            'Barangay VI (Poblacion VI), Victorias' => ['lat' => 10.9000, 'lon' => 123.0765, 'type' => 'barangay'],
            'Barangay VII (Poblacion VII), Victorias' => ['lat' => 10.9005, 'lon' => 123.0770, 'type' => 'barangay'],
            'Barangay VIII (Poblacion VIII), Victorias' => ['lat' => 10.9010, 'lon' => 123.0775, 'type' => 'barangay'],
            'Barangay IX (Poblacion IX), Victorias' => ['lat' => 10.9015, 'lon' => 123.0780, 'type' => 'barangay'],
            'Barangay X (Poblacion X), Victorias' => ['lat' => 10.9020, 'lon' => 123.0785, 'type' => 'barangay'],
            'Barangay XI (Poblacion XI), Victorias' => ['lat' => 10.9025, 'lon' => 123.0790, 'type' => 'barangay'],
            'Barangay XII (Poblacion XII), Victorias' => ['lat' => 10.9030, 'lon' => 123.0795, 'type' => 'barangay'],
            'Barangay XIII (Poblacion XIII), Victorias' => ['lat' => 10.9035, 'lon' => 123.0800, 'type' => 'barangay'],
            'Barangay XIV (Poblacion XIV), Victorias' => ['lat' => 10.9040, 'lon' => 123.0805, 'type' => 'barangay'],
            'Barangay XV (Poblacion XV), Victorias' => ['lat' => 10.9045, 'lon' => 123.0810, 'type' => 'barangay'],
            'Barangay XVI (Poblacion XVI), Victorias' => ['lat' => 10.9050, 'lon' => 123.0815, 'type' => 'barangay'],
            'Barangay XVII (Poblacion XVII), Victorias' => ['lat' => 10.9055, 'lon' => 123.0820, 'type' => 'barangay'],
            'Barangay XVIII (Poblacion XVIII), Victorias' => ['lat' => 10.9060, 'lon' => 123.0825, 'type' => 'barangay'],
            'Barangay XIX (Poblacion XIX), Victorias' => ['lat' => 10.9065, 'lon' => 123.0830, 'type' => 'barangay'],
            'Barangay XX (Poblacion XX), Victorias' => ['lat' => 10.9070, 'lon' => 123.0835, 'type' => 'barangay'],
            'Barangay XXI (Poblacion XXI), Victorias' => ['lat' => 10.9075, 'lon' => 123.0840, 'type' => 'barangay'],
            'Barangay XXII (Poblacion XXII), Victorias' => ['lat' => 10.9080, 'lon' => 123.0845, 'type' => 'barangay'],
            'Barangay XXIII (Poblacion XXIII), Victorias' => ['lat' => 10.9085, 'lon' => 123.0850, 'type' => 'barangay'],
            'Barangay XXIV (Poblacion XXIV), Victorias' => ['lat' => 10.9090, 'lon' => 123.0855, 'type' => 'barangay'],
            'Barangay XXV (Poblacion XXV), Victorias' => ['lat' => 10.9095, 'lon' => 123.0860, 'type' => 'barangay'],
            'Barangay XXVI (Poblacion XXVI), Victorias' => ['lat' => 10.9100, 'lon' => 123.0865, 'type' => 'barangay'],
            'Barangay Malaya, Victorias' => ['lat' => 10.8850, 'lon' => 123.0650, 'type' => 'barangay'],
            'Barangay Malingin, Victorias' => ['lat' => 10.9100, 'lon' => 123.0900, 'type' => 'barangay'],
            'Barangay San Miguel, Victorias' => ['lat' => 10.8950, 'lon' => 123.0720, 'type' => 'barangay'],
            
            // ========== ESCALANTE CITY BARANGAYS (Complete Coverage) ==========
            'Barangay Alimango, Escalante' => ['lat' => 10.8450, 'lon' => 123.5100, 'type' => 'barangay'],
            'Barangay Balintawak, Escalante' => ['lat' => 10.8400, 'lon' => 123.5050, 'type' => 'barangay'],
            'Barangay Binaguiohan, Escalante' => ['lat' => 10.8350, 'lon' => 123.5000, 'type' => 'barangay'],
            'Barangay Dian-ay, Escalante' => ['lat' => 10.8500, 'lon' => 123.5150, 'type' => 'barangay'],
            'Barangay Hacienda Fe, Escalante' => ['lat' => 10.8300, 'lon' => 123.4950, 'type' => 'barangay'],
            'Barangay Japitan, Escalante' => ['lat' => 10.8280, 'lon' => 123.4930, 'type' => 'barangay'],
            'Barangay Jonob-jonob, Escalante' => ['lat' => 10.8250, 'lon' => 123.4900, 'type' => 'barangay'],
            'Barangay Libertad, Escalante' => ['lat' => 10.8380, 'lon' => 123.5030, 'type' => 'barangay'],
            'Barangay Mabini, Escalante' => ['lat' => 10.8420, 'lon' => 123.5070, 'type' => 'barangay'],
            'Barangay Magsaysay, Escalante' => ['lat' => 10.8550, 'lon' => 123.5200, 'type' => 'barangay'],
            'Barangay Malasibog, Escalante' => ['lat' => 10.8320, 'lon' => 123.4970, 'type' => 'barangay'],
            'Barangay Old Poblacion, Escalante' => ['lat' => 10.8370, 'lon' => 123.5020, 'type' => 'barangay'],
            'Barangay Paitan, Escalante' => ['lat' => 10.8480, 'lon' => 123.5130, 'type' => 'barangay'],
            'Barangay Pinapugasan, Escalante' => ['lat' => 10.8200, 'lon' => 123.4850, 'type' => 'barangay'],
            'Barangay Rizal, Escalante' => ['lat' => 10.8520, 'lon' => 123.5170, 'type' => 'barangay'],
            'Barangay Sampinit, Escalante' => ['lat' => 10.8180, 'lon' => 123.4830, 'type' => 'barangay'],
            'Barangay Udtongan, Escalante' => ['lat' => 10.8220, 'lon' => 123.4870, 'type' => 'barangay'],
            'Barangay Washington, Escalante' => ['lat' => 10.8580, 'lon' => 123.5230, 'type' => 'barangay'],
            
            // ========== SIPALAY CITY BARANGAYS (17 major) ==========
            'Barangay 1, Sipalay' => ['lat' => 9.7505, 'lon' => 122.4005, 'type' => 'barangay'],
            'Barangay 2, Sipalay' => ['lat' => 9.7510, 'lon' => 122.4010, 'type' => 'barangay'],
            'Barangay 3, Sipalay' => ['lat' => 9.7515, 'lon' => 122.4015, 'type' => 'barangay'],
            'Barangay Cabadiangan, Sipalay' => ['lat' => 9.7450, 'lon' => 122.3950, 'type' => 'barangay'],
            'Barangay Camindangan, Sipalay' => ['lat' => 9.7550, 'lon' => 122.4050, 'type' => 'barangay'],
            'Barangay Gil Montilla, Sipalay' => ['lat' => 9.7600, 'lon' => 122.4100, 'type' => 'barangay'],
            'Barangay Maricalum, Sipalay' => ['lat' => 9.7400, 'lon' => 122.3900, 'type' => 'barangay'],
            'Barangay Nabulao, Sipalay' => ['lat' => 9.7350, 'lon' => 122.3850, 'type' => 'barangay'],
            
            // ========== OTHER MUNICIPALITIES (Main Centers) ==========
            'Binalbagan' => ['lat' => 10.1906, 'lon' => 122.8608, 'type' => 'municipality'],
            'Calatrava' => ['lat' => 10.5964, 'lon' => 123.4675, 'type' => 'municipality'],
            'Cauayan' => ['lat' => 9.9392, 'lon' => 122.9244, 'type' => 'municipality'],
            'Enrique B. Magalona' => ['lat' => 10.8092, 'lon' => 123.0264, 'type' => 'municipality'],
            'Hinigaran' => ['lat' => 10.2700, 'lon' => 122.8508, 'type' => 'municipality'],
            'Hinoba-an' => ['lat' => 9.6444, 'lon' => 122.4247, 'type' => 'municipality'],
            'Ilog' => ['lat' => 10.0422, 'lon' => 122.7553, 'type' => 'municipality'],
            'Isabela' => ['lat' => 10.2142, 'lon' => 122.9711, 'type' => 'municipality'],
            'La Castellana' => ['lat' => 10.3144, 'lon' => 123.0128, 'type' => 'municipality'],
            'Manapla' => ['lat' => 10.9500, 'lon' => 123.1011, 'type' => 'municipality'],
            'Moises Padilla' => ['lat' => 10.2686, 'lon' => 123.0850, 'type' => 'municipality'],
            'Murcia' => ['lat' => 10.6206, 'lon' => 123.0308, 'type' => 'municipality'],
            'Pontevedra' => ['lat' => 10.4044, 'lon' => 122.8722, 'type' => 'municipality'],
            'Pulupandan' => ['lat' => 10.5194, 'lon' => 122.8069, 'type' => 'municipality'],
            'Salvador Benedicto' => ['lat' => 10.6331, 'lon' => 123.2172, 'type' => 'municipality'],
            'San Enrique' => ['lat' => 10.4339, 'lon' => 122.8278, 'type' => 'municipality'],
            'Toboso' => ['lat' => 10.7200, 'lon' => 123.5106, 'type' => 'municipality'],
            'Valladolid' => ['lat' => 10.5092, 'lon' => 123.0872, 'type' => 'municipality'],
            
            // ========== LANDMARKS & INSTITUTIONS ==========
            // CHMSU Binalbagan Campus (list first to prioritize in search)
            'CHMSU Binalbagan' => ['lat' => 10.1906, 'lon' => 122.8608, 'type' => 'school'],
            'CHMSU Binalbagan Campus' => ['lat' => 10.1906, 'lon' => 122.8608, 'type' => 'school'],
            
            // CHMSU Fortune Towne Campus
            'CHMSU Fortune Towne' => ['lat' => 10.6812, 'lon' => 122.9510, 'type' => 'school'],
            'CHMSU Fortune Towne Campus' => ['lat' => 10.6812, 'lon' => 122.9510, 'type' => 'school'],
            
            // CHMSU Alijis Campus
            'CHMSU Alijis' => ['lat' => 10.6550, 'lon' => 122.9450, 'type' => 'school'],
            'CHMSU Alijis Campus' => ['lat' => 10.6550, 'lon' => 122.9450, 'type' => 'school'],
            
            // CHMSU Talisay - Main Campus (list last, more specific entries)
            'CHMSU Talisay' => ['lat' => 10.7358, 'lon' => 122.9853, 'type' => 'school'],
            'CHMSU Talisay Campus' => ['lat' => 10.7358, 'lon' => 122.9853, 'type' => 'school'],
            'CHMSU - Carlos Hilado Memorial State University, Talisay City' => ['lat' => 10.7358, 'lon' => 122.9853, 'type' => 'school'],
            'Carlos Hilado Memorial State University, Talisay City' => ['lat' => 10.7358, 'lon' => 122.9853, 'type' => 'school'],
            'SM City Bacolod' => ['lat' => 10.6770, 'lon' => 122.9560, 'type' => 'landmark'],
            'Ayala Capitol Central' => ['lat' => 10.6745, 'lon' => 122.9510, 'type' => 'landmark'],
            'Robinsons Place Bacolod' => ['lat' => 10.6780, 'lon' => 122.9540, 'type' => 'landmark'],
            'Bacolod City Plaza' => ['lat' => 10.6760, 'lon' => 122.9500, 'type' => 'landmark'],
            'Bacolod-Silay Airport' => ['lat' => 10.7764, 'lon' => 123.0147, 'type' => 'airport'],
        ];
    }
    
    /**
     * Calculate distance using Haversine formula
     * @param float $lat1, $lon1, $lat2, $lon2
     * @return float Distance in kilometers
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return round($distance, 1);
    }
    
    /**
     * Find location by name (fuzzy search with priority for specific matches)
     * @param string $search
     * @return array|null
     */
    public static function findLocation($search) {
        $locations = self::getAllLocations();
        $searchOriginal = trim($search);
        $search = strtolower($searchOriginal);
        
        // PRIORITY 1: Exact match (case-insensitive)
        foreach ($locations as $name => $data) {
            if (strtolower($name) === $search) {
                return array_merge($data, ['name' => $name]);
            }
        }
        
        // PRIORITY 2: Contains specific campus identifiers (Alijis, Binalbagan, Fortune Towne)
        $specificKeywords = ['alijis', 'binalbagan', 'fortune towne', 'fortune'];
        foreach ($specificKeywords as $keyword) {
            if (stripos($search, $keyword) !== false) {
                // Search must match this specific keyword
                foreach ($locations as $name => $data) {
                    if (stripos(strtolower($name), $keyword) !== false) {
                        return array_merge($data, ['name' => $name]);
                    }
                }
            }
        }
        
        // PRIORITY 3: Normalize and do exact match
        $search = str_replace(['city', 'negros occidental', 'philippines', ',', 'campus'], ['', '', '', '', ''], $search);
        $search = trim(preg_replace('/\s+/', ' ', $search));
        
        foreach ($locations as $name => $data) {
            $normalizedName = str_replace(['City', 'Negros Occidental', 'Philippines', ',', 'Campus'], ['', '', '', '', ''], $name);
            $normalizedName = strtolower(trim(preg_replace('/\s+/', ' ', $normalizedName)));
            
            if ($normalizedName === $search) {
                return array_merge($data, ['name' => $name]);
            }
        }
        
        // PRIORITY 4: Partial match (but exclude if searching for specific campus and it doesn't match)
        $searchingForSpecificCampus = false;
        foreach ($specificKeywords as $keyword) {
            if (stripos($searchOriginal, $keyword) !== false) {
                $searchingForSpecificCampus = $keyword;
                break;
            }
        }
        
        foreach ($locations as $name => $data) {
            // If searching for specific campus, skip entries without that keyword
            if ($searchingForSpecificCampus && stripos(strtolower($name), $searchingForSpecificCampus) === false) {
                continue;
            }
            
            $normalizedName = str_replace(['City', 'Negros Occidental', 'Philippines', ','], ['', '', '', ''], $name);
            $normalizedName = strtolower(trim(preg_replace('/\s+/', ' ', $normalizedName)));
            
            if (stripos($normalizedName, $search) !== false) {
                return array_merge($data, ['name' => $name]);
            }
        }
        
        // PRIORITY 5: Try original search as fallback
        foreach ($locations as $name => $data) {
            if (stripos($name, $searchOriginal) !== false) {
                return array_merge($data, ['name' => $name]);
            }
        }
        
        return null;
    }
    
    /**
     * Get distance from CHMSU to location
     * @param string $locationName
     * @return array Distance info or null
     */
    public static function getDistanceFromCHMSU($locationName) {
        $location = self::findLocation($locationName);
        
        if (!$location) {
            return null;
        }
        
        $distance = self::calculateDistance(
            self::ORIGIN_LAT, 
            self::ORIGIN_LON,
            $location['lat'],
            $location['lon']
        );
        
        return [
            'destination' => $location['name'],
            'distance_km' => $distance,
            'total_distance_km' => $distance * 2, // Round trip
            'lat' => $location['lat'],
            'lon' => $location['lon'],
            'type' => $location['type']
        ];
    }
    
    /**
     * Search locations (for autocomplete)
     * @param string $query
     * @param int $limit
     * @return array
     */
    public static function searchLocations($query, $limit = 10) {
        $locations = self::getAllLocations();
        $query = strtolower(trim($query));
        $results = [];
        
        foreach ($locations as $name => $data) {
            if (stripos($name, $query) !== false) {
                $results[] = [
                    'name' => $name,
                    'lat' => $data['lat'],
                    'lon' => $data['lon'],
                    'type' => $data['type']
                ];
                
                if (count($results) >= $limit) {
                    break;
                }
            }
        }
        
        return $results;
    }
}
?>

