<?php
namespace MockOneiss\Model;

/**
 * Class PushInjuryData
 * Strongly-typed model for pushInjuryData WSDL generation and documentation.
 */
class PushInjuryData
{
    /** @var string */ public $Pat_Facility_No;
    /** @var string */ public $Status;
    /** @var string */ public $rstatuscode;
    /** @var \DateTime|string */ public $date_report;
    /** @var string */ public $time_report;
    /** @var string */ public $reg_no;
    /** @var string */ public $tempreg_no;
    /** @var int */ public $hosp_no;
    /** @var string */ public $hosp_reg_no;
    /** @var string */ public $hosp_cas_no;
    /** @var string */ public $ptype_code;
    /** @var string|null */ public $Pat_Last_Name;
    /** @var string|null */ public $Pat_First_Name;
    /** @var string|null */ public $Pat_Middle_Name;
    /** @var string|null */ public $Pat_Sex;
    /** @var \DateTime|string|null */ public $Pat_Date_of_Birth;
    /** @var string|null */ public $Pat_Current_Address_Region;
    /** @var string|null */ public $Pat_Current_Address_Province;
    /** @var string|null */ public $Pat_Current_Address_City;
    /** @var string|null */ public $temp_regcode;
    /** @var string|null */ public $temp_provcode;
    /** @var string|null */ public $temp_citycode;
    /** @var int|null */ public $Age_Years;
    /** @var int|null */ public $Age_Month;
    /** @var int|null */ public $Age_Day;
    /** @var string|null */ public $Pat_Phil_Health_No;
    /** @var string|null */ public $plc_regcode;
    /** @var string|null */ public $plc_provcode;
    /** @var string|null */ public $plc_ctycode;
    /** @var \DateTime|string|null */ public $inj_date;
    /** @var string|null */ public $inj_time;
    /** @var \DateTime|string|null */ public $Encounter_Date;
    /** @var string|null */ public $Encounter_Time;
    /** @var string|null */ public $inj_intent_code;
    /** @var string|null */ public $first_aid_code;
    /** @var string|null */ public $firstaid_others;
    /** @var string|null */ public $firstaid_others2;
    /** @var string|null */ public $mult_inj;
    // ... other fields omitted for brevity; WSDL will include major fields via annotations above
}
