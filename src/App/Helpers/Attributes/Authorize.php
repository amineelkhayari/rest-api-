<?php
namespace App\Helpers\Attributes;

use Attribute;

/**
 * Marks a controller or method as requiring authorization.
 * Optionally you can later extend this to accept roles/scopes.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Authorize
{
    /** @var string[] */
    public array $roles = [];
    /** @var string|null Controller policy method name for ABAC */
    public ?string $policy = null;

    /**
     * Authorize attribute may be used as:
     *  #[Authorize]
     *  #[Authorize(['admin','editor'])]
     *  #[Authorize('admin,editor')]
     *  #[Authorize(['admin'], 'canEdit')]
     *  #[Authorize('admin', 'canEdit')]
     *
     * @param string|array|null $roles
     * @param string|null $policy Optional controller method name used for ABAC checks; should return bool
     */
    public function __construct(string|array|null $roles = null, ?string $policy = null)
    {
        $this->policy = $policy;

        if ($roles === null) {
            $this->roles = [];
            return;
        }

        if (is_string($roles)) {
            // comma or space separated
            $parts = preg_split('/[,\s]+/', trim($roles));
            $this->roles = array_values(array_filter($parts, fn($v) => $v !== ''));
            return;
        }

        // array
        $this->roles = array_values(array_filter($roles, fn($v) => $v !== ''));
    }
}
