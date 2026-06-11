use Clickem\UrlShortener\Clickem;
use Clickem\UrlShortener\Exceptions\ApiException;

$clickem = new Clickem('seu-token-aqui');

// Encurtamento simples
$url = $clickem->shorten('https://exemplo.com/pagina-longa');
echo $url->short_url;   // https://clickem.me/aB3xY1
echo $url->code;        // aB3xY1

// Com data de expiração
$url = $clickem->shorten('https://exemplo.com/pagina-longa', '2025-12-31');

// Tratamento de erros
try {
    $url = $clickem->shorten('https://...');
} catch (ApiException $e) {
    echo $e->getStatusCode(); // 401, 422, etc.
    echo $e->getErrors();     // array com erros de validação
}

// ShortUrl implementa __toString, então:
echo (string) $url;  // https://clickem.me/aB3xY1
