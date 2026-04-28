<?php

namespace App\Events;

use App\Models\Member;
use App\Models\Membership;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Member $member,
        public Membership $membership,
    ) {}
}
