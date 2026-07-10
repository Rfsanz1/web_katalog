<?php

namespace Webkul\KledoIntegration\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models registered with Concord.
     *
     * @var array
     */
    protected $models = [];
}
