<?php

namespace App\Customization\Cake\Routing\Route;

use Cake\Utility\Inflector;

/**
 * The class is created to override method "parse" of the \Cake\Routing\Route\DashedRoute class
 *
 * It helps to matching older routers
 */
class DashedRoute extends \Cake\Routing\Route\DashedRoute
{
    /**
     * Parses a string URL into an array. If it matches, it will convert the
     * controller and plugin keys to their CamelCased form and action key to
     * camelBacked form.
     *
     * @param string $url The URL to parse
     * @param string $method The HTTP method.
     *
     * @return array|null An array of request parameters, or null on failure.
     */
    public function parse(string $url, string $method = ''): ?array
    {
        $params = parent::parse($url, $method);
        if (!$params) {
            return null;
        }
        if (!empty($params['controller'])) {
            $params['controller'] = Inflector::camelize($params['controller'], '_');
        }
        if (!empty($params['plugin'])) {
            $params['plugin'] = $this->_camelizePlugin($params['plugin']);
        }
        if (!empty($params['action'])) {
            $params['action'] = Inflector::variable(str_replace(
                '-',
                '_',
                $params['action']
            ));
        }

        return $params;
    }

    /**
     * Helper method for dasherizing keys in a URL array.
     *
     * @param array $url An array of URL keys.
     *
     * @return array
     */
    protected function _dasherize(array $url): array
    {
        foreach (['controller', 'plugin', 'action'] as $element) {
            if (!empty($url[$element])) {
                $url[$element] = Inflector::underscore($url[$element]);
            }
        }

        return $url;
    }
}
