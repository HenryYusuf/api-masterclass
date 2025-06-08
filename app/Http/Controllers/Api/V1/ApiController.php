<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiController extends Controller
{
    use ApiResponses;

    protected $policyClass;

    public function include(string $relationship)
    {
        $param = request()->get('include');

        if (!isset($param)) {
            return false;
        }

        $includeValue = explode('.', strtolower($param));

        return in_array(strtolower($relationship), $includeValue);
    }

    public function isAble($ability, $targetModel) {
        try {
            Gate::authorize($ability, [$targetModel, $this->policyClass]);
            return true;
        } catch (AuthenticationException $_COOKIE) {
            return false;
        }
    }
}
