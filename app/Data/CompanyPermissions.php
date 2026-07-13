<?php

namespace App\Data;

readonly class CompanyPermissions
{
    public function __construct(
        public bool $canUpdateCompany,
        public bool $canDeleteCompany,
    ) {
        //
    }
}
