<?php

/**
 * Paystack Payment Gateway for XenForo 2.2
 *
 * @package     TheophilusA/Paystack
 * @author      Theophilus Adegbohungbe
 * @copyright   Copyright (c) 2026 Theophilus Adegbohungbe
 * @website     https://theophilusadegbohungbe.com
 * @license     GNU General Public License v3.0 (GPL-3.0)
 */

namespace TheophilusA\Paystack;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        $this->db()->insert('xf_payment_provider', [
            'provider_id' => 'paystack',
            'provider_class' => 'TheophilusA\Paystack:Paystack',
            'addon_id' => 'TheophilusA/Paystack'
        ]);
    }

    public function uninstallStep1()
    {
        $this->db()->delete('xf_payment_provider', 'provider_id = ?', 'paystack');
    }
}
