<?php

namespace App\Enums;

enum CompanyPermission: string
{
    case UpdateCompany = 'company:update';
    case DeleteCompany = 'company:delete';

    case RecordTransactions = 'finance:record';
    case ManageFinanceSetup = 'finance:configure';

}
