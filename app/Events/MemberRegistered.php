<?php

namespace App\Events;

use App\Models\Member;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Member $member,
    ) {}
}
