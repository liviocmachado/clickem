<p align="center">
  <img src="https://raw.githubusercontent.com/liviocmachado/clickem/refs/heads/main/logo.png">
</p>
  
Run composer:
```sh
composer require clickem/url-shortener
```
  
use Clickem\UrlShortener\Clickem;  
use Clickem\UrlShortener\Exceptions\ApiException;  
    
$clickem = new Clickem('YOUR-TOKEN');
    
// Simple shortening   
$url = $clickem->shorten('https://example.com/long-page');  
echo $url->short_url;   // https://clickem.me/aB3xY1  
echo $url->code;        // aB3xY1  
    
// With an expiration date  
$url = $clickem->shorten('https://example.com/long-page', '2025-12-31');
    

try {   
    $url = $clickem->shorten('https://...');    
} catch (ApiException $e) {  
    echo $e->getStatusCode();  
    echo $e->getErrors();  
}  
    
// ShortUrl:  
echo (string) $url;  // https://clickem.me/aB3xY1
