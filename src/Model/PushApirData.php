<?php
namespace MockOneiss\Model;

/**
 * Class PushApirData
 * Strongly-typed model for pushApirData WSDL generation and documentation.
 */
class PushApirData
{
    /** @var string */ public $Pat_Facility_No;
    /** @var string|null */ public $reg_no;
    /** @var string|null */ public $tempreg_no;
    /** @var string|null */ public $sentinel;
    /** @var \DateTime|string|null */ public $date_report;
    /** @var string|null */ public $time_report;
    /** @var string */ public $Pat_Last_Name;
    /** @var string */ public $Pat_First_Name;
    /** @var string */ public $Pat_Middle_Name;
    /** @var string|null */ public $Pat_Suffix;
    /** @var \DateTime|string|null */ public $Pat_Date_of_Birth;
    /** @var int|null */ public $Age_Years;
    /** @var int|null */ public $Age_Months;
    /** @var int|null */ public $Age_Days;
    /** @var string */ public $Pat_Sex;
    /** @var string */ public $Pat_Current_Address_StreetName;
    /** @var string */ public $Pat_Current_Address_Region;
    /** @var string */ public $Pat_Current_Address_Province;
    /** @var string */ public $Pat_Current_Address_City;
    /** @var string|null */ public $Pat_Current_Address_Barangay;
    /** @var string|null */ public $telephone_no;
    /** @var string|null */ public $four_ps_member;
    /** @var \DateTime|string */ public $inj_date;
    /** @var string */ public $inj_time;
    /** @var string|null */ public $plc_pat_str;
    /** @var string|null */ public $poi_sameadd;
    /** @var string|null */ public $poi_regcode;
    /** @var string|null */ public $poi_provcode;
    /** @var string|null */ public $poi_citycode;
    /** @var string|null */ public $poi_bgycode;
    /** @var string|null */ public $place_of_occurence;
    /** @var string|null */ public $place_of_occurence_others;
    /** @var \DateTime|string */ public $Encounter_Date;
    /** @var string */ public $Encounter_Time;
    /** @var string|null */ public $referral;
    /** @var string|null */ public $reffered_from;
    /** @var string */ public $involve_code;
    /** @var string */ public $typeof_injurycode;
    /** @var string|null */ public $injury_type_others;
    /** @var string|null */ public $mult_inj;
    /** @var string|null */ public $if_fireworks_related;
    /** @var string|null */ public $if_fireworks_related_2;
    /** @var string|null */ public $if_fireworks_related_3;
    /** @var string */ public $diagnosis;
    /** @var string|null */ public $analoc_eye;
    /** @var string|null */ public $analoc_head;
    /** @var string|null */ public $analoc_neck;
    /** @var string|null */ public $analoc_chest;
    /** @var string|null */ public $analoc_back;
    /** @var string|null */ public $analoc_abdomen;
    /** @var string|null */ public $analoc_buttocks;
    /** @var string|null */ public $analoc_hand;
    /** @var string|null */ public $analoc_forearmarm;
    /** @var string|null */ public $analoc_pelvic;
    /** @var string|null */ public $analoc_thigh;
    /** @var string|null */ public $analoc_knee;
    /** @var string|null */ public $analoc_legs;
    /** @var string|null */ public $analoc_foot;
    /** @var string|null */ public $firecracker_code;
    /** @var string|null */ public $firecracker_others;
    /** @var string|null */ public $firecracker_legality;
    /** @var string */ public $liquor;
    /** @var string|null */ public $treatment_code;
    /** @var string|null */ public $treatment_code2;
    /** @var string|null */ public $treatment_code3;
    /** @var string|null */ public $given_others;
    /** @var string */ public $disposition_code;
    /** @var string|null */ public $transferred_to;
    /** @var string|null */ public $transferred_to_sp;
    /** @var string|null */ public $outcome_code;
    /** @var \DateTime|string|null */ public $date_died;
    /** @var string|null */ public $aware;
    /** @var string|null */ public $educ_material;
    /** @var string|null */ public $fac_regno;
    /** @var string|null */ public $Facility_Reg;
    /** @var string|null */ public $Facility_Prov;
    /** @var string|null */ public $Facility_CityMun;
}
