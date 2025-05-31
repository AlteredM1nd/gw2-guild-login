<?php
// This file is obsolete. The class has been replaced by includes/GW2_2FA_Handler.php for PSR-4 compliance.
class GW2_2FA_Handler {
    public function __call($name, $arguments) {
        throw new \Exception('GW2_2FA_Handler (legacy) is obsolete. Use GW2GuildLogin\\GW2_2FA_Handler.');
    }
}
