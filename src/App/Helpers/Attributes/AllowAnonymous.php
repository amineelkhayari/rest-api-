<?php
namespace App\Helpers\Attributes;

use Attribute;

/**
 * Marks a controller or method as allowing anonymous access (no auth required).
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class AllowAnonymous
{
    // marker attribute
}
