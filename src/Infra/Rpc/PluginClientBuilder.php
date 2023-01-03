<?php

declare(strict_types=1);

namespace Gnumoksha\FreeIpa\Infra\Rpc;

use Gnumoksha\FreeIpa\Infra\Rpc\Response\BodyBuilder as ResponseBodyBuilder;
use Gnumoksha\FreeIpa\Infra\Rpc\Response\CommonBodyBuilder;
use Gnumoksha\FreeIpa\Options;
use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\BaseUriPlugin;
use Http\Client\Common\Plugin\CookiePlugin;
use Http\Client\Common\Plugin\HeaderSetPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Message\CookieJar;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\UriFactoryInterface;
use RuntimeException;

/**
 * Builds a HTTP RPC Client using HTTP-Plug plugins.
 */
class PluginClientBuilder implements ClientBuilder
{
    private Options $options;

    private ?ClientInterface $psrHttpClient;

    private UriFactoryInterface $uriFactory;

    private ResponseBodyBuilder|CommonBodyBuilder $responseBodyBuilder;

    private array $httpClientPlugins;

    public function __construct(
        Options $options,
        ClientInterface $psrHttpClient = null,
        ?UriFactoryInterface $uriFactory = null,
        ?ResponseBodyBuilder $responseBodyBuilder = null
    ) {
        $this->options             = $options;
        $this->psrHttpClient       = $psrHttpClient;
        $this->uriFactory          = $uriFactory ?? Psr17FactoryDiscovery::findUrlFactory();
        $this->responseBodyBuilder = $responseBodyBuilder ?? new CommonBodyBuilder();
    }

    public function build(): Client
    {
        return new Client(
            $this->options,
            $this->buildPluginClient(),
            Psr17FactoryDiscovery::findRequestFactory(),
            $this->responseBodyBuilder
        );
    }

    /**
     * @see https://access.redhat.com/articles/2728021#end-points documentation
     */
    private function buildPluginClient(): PluginClient
    {
        $cookieJar = new CookieJar();
        $this->addHttpClientPlugin(new HeaderSetPlugin([
            'Referer'    => $this->options->getPrimaryUrl(),
            'User-Agent' => 'php-FreeIPA',
        ]));
        $this->addHttpClientPlugin(new BaseUriPlugin($this->uriFactory->createUri($this->options->getPrimaryUrl())));
        $this->addHttpClientPlugin(new CookiePlugin($cookieJar));

        $psrHttpClient = $this->psrHttpClient;
        if ($psrHttpClient === null) {
            // #TODO setup docs.php-http.org/en/latest/clients/socket-client.html too
            if (!class_exists('\Http\Client\Curl\Client')) {
                throw new RuntimeException('Specify a HTTP client or install php-http/curl-client');
            }

            $psrHttpClient = new \Http\Client\Curl\Client(
                null,
                null,
                [
                    CURLOPT_CAINFO => $this->options->getCertificatePath(),
                ]
            );
        }

        return new PluginClient($psrHttpClient, $this->httpClientPlugins);
    }

    public function addHttpClientPlugin(Plugin $plugin): self
    {
        $this->httpClientPlugins[] = $plugin;

        return $this;
    }
}
