<?php
/** ---------------------------------------------------------------------
<<<<<<<< HEAD:app/lib/TusConfig.php
 * app/lib/TusConfig.php : 
========
 * themes/default/views/mediaViewers/UniversalViewerAutocomplete.php :
>>>>>>>> 4526af800 (FOBDSOP-88 Adição do tema bienal):themes/bienal/views/mediaViewers/UniversalViewerAutocomplete.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
<<<<<<<< HEAD:app/lib/TusConfig.php
 * Copyright 2024 Whirl-i-Gig
========
 * Copyright 2017 Whirl-i-Gig
>>>>>>>> 4526af800 (FOBDSOP-88 Adição do tema bienal):themes/bienal/views/mediaViewers/UniversalViewerAutocomplete.php
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
<<<<<<<< HEAD:app/lib/TusConfig.php
 * @subpackage MediaUploader
========
 * @subpackage Media
>>>>>>>> 4526af800 (FOBDSOP-88 Adição do tema bienal):themes/bienal/views/mediaViewers/UniversalViewerAutocomplete.php
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
<<<<<<<< HEAD:app/lib/TusConfig.php
return [
	// Tus server cache connection parameters
    'redis' => [
        'host' => getenv('REDIS_HOST') !== false ? getenv('REDIS_HOST') : (defined('__CA_REDIS_HOST__') ? __CA_REDIS_HOST__ : '127.0.0.1'),
        'port' => getenv('REDIS_PORT') !== false ? getenv('REDIS_PORT') : (defined('__CA_REDIS_PORT__') ? __CA_REDIS_PORT__ : '6379'),
        'database' => getenv('REDIS_DB') !== false ? getenv('REDIS_DB') : (defined('__CA_REDIS_DB__') ? __CA_REDIS_DB__ : 0),
    ],
    'file' => [
        'dir' => __CA_APP_DIR__ .'/tmp/tuscache/',
        'name' => 'tus_php.server.cache',
    ],
];
========
 
    $va_matches = $this->getVar('matches');
    
    $vs_url = caNavUrl($this->request, '*', '*', '*', ['q' => $this->request->getParameter('q', pString)]);
    if (is_array($va_matches)) {
        $va_matches = array_map(function($v) use ($vs_url) {
            return [
                'match' => $v,
                'search' => $vs_url
            ];
        }, $va_matches);
    }
 
    $va_manifest = [
      "@context" => "http://iiif.io/api/search/0/context.json",
      "@id" => $vs_url,
      "@type" => "search:TermList",
      "terms" => $va_matches
    ];
    
    print json_encode($va_manifest);
>>>>>>>> 4526af800 (FOBDSOP-88 Adição do tema bienal):themes/bienal/views/mediaViewers/UniversalViewerAutocomplete.php
