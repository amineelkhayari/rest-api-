<?php
namespace App\Helpers;

use Attribute;

// Marker attribute to indicate a class is an API controller and should be
// discovered/registered automatically by the AttributeRouteLoader.
#[Attribute(Attribute::TARGET_CLASS)]
class ApiController
{
    // no properties needed for now; this is a marker attribute
}
