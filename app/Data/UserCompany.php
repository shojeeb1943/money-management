<?php

namespace App\Data;

readonly class UserCompany
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public bool $isPersonal,
        public ?string $role,
        public ?string $roleLabel,
        public ?bool $isCurrent = null,
        public string $timezone = 'Asia/Dhaka',
        public string $currency = 'BDT',
    ) {
        //
    }
}
