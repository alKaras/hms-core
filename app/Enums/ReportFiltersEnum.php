<?php

namespace App\Enums;

enum ReportFiltersEnum: string
{
    case HOSPITAL_REPORT = "HospitalReport";
    case DOCTOR_REPORT = "DoctorReport";
    case GENERAL_REPORT = "GeneralReport";
    case DETAILED_REPORT = "DetailedReport";
    case HOSPITAL_STATS_REPORT = "HospitalStats";


}