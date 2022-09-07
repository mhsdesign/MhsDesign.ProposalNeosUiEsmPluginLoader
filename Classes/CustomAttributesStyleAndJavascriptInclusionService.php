<?php

namespace MhsDesign\ProposalNeosUiEsmPluginLoader;

use Neos\Eel\CompilingEvaluator;
use Neos\Eel\Utility;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Utility\PositionalArraySorter;

/**
 * @Flow\Scope("singleton")
 */
class CustomAttributesStyleAndJavascriptInclusionService
{
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject(lazy=false)
     * @var CompilingEvaluator
     */
    protected $eelEvaluator;

    /**
     * @Flow\InjectConfiguration(package="Neos.Fusion", path="defaultContext")
     * @var array
     */
    protected $fusionDefaultEelContext;

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos.Ui", path="configurationDefaultEelContext")
     * @var array
     */
    protected $additionalEelDefaultContext;

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos.Ui", path="resources.javascript")
     * @var array
     */
    protected $javascriptResources;

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos.Ui", path="resources.stylesheets")
     * @var array
     */
    protected $stylesheetResources;

    public function getHeadScripts()
    {
        return $this->build($this->javascriptResources, function ($uri, $additionalAttributes) {
            return '<script src="' . $uri . '" ' . $additionalAttributes . '></script>';
        });
    }

    public function getHeadStylesheets()
    {
        return $this->build($this->stylesheetResources, function ($uri, $additionalAttributes) {
            return '<link rel="stylesheet" href="' . $uri . '" ' . $additionalAttributes . '/>';
        });
    }

    protected function build(array $resourceArrayToSort, \Closure $builderForLine)
    {
        $sortedResources = (new PositionalArraySorter($resourceArrayToSort))->toArray();

        $result = '';
        foreach ($sortedResources as $element) {
            $resourceExpression = $element['resource'];
            if (substr($resourceExpression, 0, 2) === '${' && substr($resourceExpression, -1) === '}') {
                $resourceExpression = Utility::evaluateEelExpression(
                    $resourceExpression,
                    $this->eelEvaluator,
                    [],
                    array_merge($this->fusionDefaultEelContext, $this->additionalEelDefaultContext)
                );
            }

            $hash = null;

            if (strpos($resourceExpression, 'resource://') === 0) {
                // Cache breaker
                $hash = substr(md5_file($resourceExpression), 0, 8);
                $resourceExpression = $this->resourceManager->getPublicPackageResourceUriByPath($resourceExpression);
            }
            $finalUri = $hash ? $resourceExpression . '?' . $hash : $resourceExpression;
            $additionalAttributes = array_merge(
                // legacy first level 'defer' attribute
                isset($element['defer']) ? ['defer' => $element['defer']] : [],
                $element['attributes'] ?? []
            );
            $result .= $builderForLine($finalUri, $this->htmlAttributesArrayToString($additionalAttributes));
        }
        return $result;
    }

    /**
     * @param array<string,string|bool> $attributes 
     */
    private function htmlAttributesArrayToString(array $attributes): string
    {
        return join(' ', array_filter(array_map(function($key, $value) {
            if (is_bool($value)) {
               return $value ? htmlspecialchars($key) : null;
            }
            return htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }, array_keys($attributes), $attributes)));
    }
}
