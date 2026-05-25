<?php

namespace App\Services\AI;

use App\DTOs\EmailInterpretationResult;

interface EmailTaskInterpreter
{
    public function interpret(string $sender, string $subject, string $body): EmailInterpretationResult;
}
