Run composer:
```sh
composer require clickem/url-shortener
```
  
use Clickem\UrlShortener\Clickem;  
use Clickem\UrlShortener\Exceptions\ApiException;  
    
$clickem = new Clickem('seu-token-aqui');
    
// Simple shortening
$url = $clickem->shorten('https://exemplo.com/pagina-longa');  
echo $url->short_url;   // https://clickem.me/aB3xY1  
echo $url->code;        // aB3xY1  
    
// With an expiration date  
$url = $clickem->shorten('https://exemplo.com/pagina-longa', '2025-12-31');
    
// Error handling 
try {  
    $url = $clickem->shorten('https://...');  
} catch (ApiException $e) {  
    echo $e->getStatusCode(); // 401, 422, etc.  
    echo $e->getErrors();     // array com erros de validação  
}  
    
// ShortUrl:  
echo (string) $url;  // https://clickem.me/aB3xY1
