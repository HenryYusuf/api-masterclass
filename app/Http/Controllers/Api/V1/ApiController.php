<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
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
        // dd($ability, $targetModel);
        return Gate::authorize($ability, [$targetModel, $this->policyClass]);
    }
}
