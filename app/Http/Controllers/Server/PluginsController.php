<?php

namespace Pterodactyl\Http\Controllers\Server;

use GuzzleHttp\Client;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Pterodactyl\Repositories\Daemon\PluginsRepository;
use Pterodactyl\Traits\Controllers\JavascriptInjection;

class PluginsController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Pterodactyl\Repositories\Daemon\PluginsRepository
     */
    protected $pluginsRepositroy;

    /**
     * PluginsController constructor.
     * @param AlertsMessageBag $alert
     * @param PluginsRepository $pluginsRepository
     */
    public function __construct(AlertsMessageBag $alert, PluginsRepository $pluginsRepository)
    {
        $this->alert = $alert;
        $this->pluginsRepositroy = $pluginsRepository;
    }

    /**
     * @param Request $request
     * @return View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request): View
    {
        $server = $request->attributes->get('server');
        $this->authorize('view-plugins', $server);
        $this->setRequest($request)->injectJavascript();

        $installedPlugins = DB::table('plugins')->where('server_id', '=', $server->id)->get();

        return view('server.plugins', [
            'installedPlugins' => $installedPlugins
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $server = $request->attributes->get('server');

        try {
            $this->authorize('view-plugins', $server);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'error' => 'You don\'t have an access to this page.']);
        }

        $query = trim(strip_tags($request->input('query')));
        $page = (int) $request->input('page');
        $size = (int) $request->input('size');

        if (empty($query)) {
            $uri = 'https://api.spiget.org/v2/resources?size=' . $size . '&page=' . $page;
        } else {
            $uri = 'https://api.spiget.org/v2/search/resources/' . urlencode($query) . '?size=' . $size . '&page=' . $page;
        }

        try {
            $client = new Client();
            $res = $client->request('GET', $uri, [
                'headers' => [
                    'User-Agent' => 'pterodactyl-plugin-manager/1.0',
                ]
            ]);
        } catch (GuzzleException $e) {
            return response()->json([
                'success' => false,
                'response' => [],
                'total' => 0,
                'max_page' => 0,
                'page' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'response' => json_decode($res->getBody(), true),
            'total' => $res->getHeaders()['X-Total'][0],
            'max_page' => $res->getHeaders()['X-Page-Count'][0],
            'page' => $res->getHeaders()['X-Page-Index'][0],
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function download(Request $request): JsonResponse
    {
        $server = $request->attributes->get('server');

        try {
            $this->authorize('view-plugins', $server);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'error' => 'You don\'t have an access to this page.']);
        }

        $id = trim($request->input('id'));

        $issetPlugin = DB::table('plugins')->where('server_id', '=', $server->id)->where('plugin_id', '=', $id)->get();
        if (count($issetPlugin) > 0) {
            return response()->json(['success' => false, 'error' => 'This plugin installed to your server!']);
        }

        try {
            $response = $this->pluginsRepositroy->setServer($server)->download([
                'id' => $id
            ]);
        } catch (RequestException $e) {
            return response()->json(['success' => false, 'error' => 'Failed to install this plugin.']);
        }

        if (json_decode($response->getBody())->success != "true") {
            return response()->json(['success' => false, 'error' => 'The plugin could not be installed!']);
        }

        DB::table('plugins')->insert([
            "server_id" => $server->id, "plugin_id" => $id, 'plugin_name' => json_decode($response->getBody())->name
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function remove(Request $request): JsonResponse
    {
        $server = $request->attributes->get('server');

        try {
            $this->authorize('view-plugins', $server);
        } catch (AuthorizationException $e) {
            return response()->json(['success' => false, 'error' => 'You don\'t have an access to this page.']);
        }

        $id = trim($request->input('id'));

        $plugin = DB::table('plugins')->where('server_id', '=', $server->id)->where('plugin_id', '=', $id)->get();
        if (count($plugin) < 1) {
            return response()->json(['success' => false, 'error' => 'Plugin not installed to this server!']);
        }

        try {
            $this->pluginsRepositroy->setServer($server)->delete([
                'name' => $plugin[0]->plugin_name
            ]);
        } catch (RequestException $e) {
            return response()->json(['success' => false, 'error' => 'Failed to remove this plugin.']);
        }

        DB::table('plugins')->where('server_id', '=', $server->id)->where('plugin_id', '=', $id)->delete();

        return response()->json([
            'success' => true
        ]);
    }
}
