<?php

namespace App\Controller\Component;

/**
 * This class to override the class \Cake\Controller\Component\AuthComponent
 *
 */
class AuthComponent extends \Cake\Controller\Component\AuthComponent
{
    /**
     * The query string key used for remembering the referred page when getting
     * redirected to login.
     *
     * @var string
     */
    const QUERY_STRING_REDIRECT = 'ref';

    /**
     * Returns the URL to redirect back to or / if not possible.
     *
     * This method takes the referrer into account if the
     * request is not of type GET.
     *
     * @return string
     */
    protected function _getUrlToRedirectBackTo(): string
    {
        $urlToRedirectBackTo = $this->getController()->getRequest()->getRequestTarget();
        if (!$this->getController()->getRequest()->is('get')) {
            $urlToRedirectBackTo = $this->getController()->referer();
        }

        list ($urlToRedirectBackTo, $queryString) = explode('?', $urlToRedirectBackTo);
        parse_str($queryString, $queries);
        unset($queries['peraiche_domain_share']);
        return count($queries) > 0 ? ($urlToRedirectBackTo . '?' . http_build_query($queries)) : $urlToRedirectBackTo;
    }
}
