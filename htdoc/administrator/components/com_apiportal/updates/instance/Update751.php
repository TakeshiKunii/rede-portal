<?php
/**
 *
 */

require_once __DIR__.'/../../../../../components/com_apiportal/helpers/HandleProxyService.php';

class Update751
{
    /**
     * @param array|null $params
     */
    public function init(array $params = null)
    {
        
        // Now handle the proxy service
        try{
            $proxyService = new \HandleProxyService($params['dir']);
            $proxyService->setProxyDir();
        }
        catch (Exception $e){
            error_log("Error during handling proxy Service : " . $e->getMessage());
        }
    }
}
