<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Application identity shown in emails and other server-rendered text.
 *
 * Override in .env, for example:
 *   branding.appName      = 'ICT Schedule System'
 *   branding.appShortName = 'ISCHED'
 *   branding.orgName      = 'Department of Health - Ilocos Center for Health Development'
 *   branding.orgShortName = 'DOH-ICHD'
 */
class Branding extends BaseConfig
{
    public string $appName      = 'ICT Schedule System';
    public string $appShortName = 'ISCHED';
    public string $orgName      = 'Department of Health - Ilocos Center for Health Development';
    public string $orgShortName = 'DOH-ICHD';
}
