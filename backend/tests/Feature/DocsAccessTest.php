<?php

namespace Tests\Feature;

use Tests\TestCase;

// la documentación de la API (/docs/api) solo se abre en local o con el usuario y la contraseña de DOCS_USER y DOCS_PASSWORD
class DocsAccessTest extends TestCase
{
    private function conClave(string $clave, string $usuario = 'docs'): array
    {
        return ['Authorization' => 'Basic '.base64_encode("{$usuario}:{$clave}")];
    }

    public function test_en_produccion_sin_contrasena_configurada_queda_cerrada(): void
    {
        config(['app.docs_password' => null]);

        $this->get('/docs/api')->assertForbidden();
        $this->get('/docs/api.json')->assertForbidden();
        // aunque alguien mande credenciales vacías, no hay nada con qué compararlas
        $this->withHeaders($this->conClave(''))->get('/docs/api')->assertForbidden();
    }

    public function test_con_contrasena_configurada_el_navegador_la_pide(): void
    {
        config(['app.docs_password' => 'clave-de-prueba']);

        $this->get('/docs/api')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate');
    }

    public function test_una_contrasena_incorrecta_no_entra(): void
    {
        config(['app.docs_password' => 'clave-de-prueba']);

        $this->withHeaders($this->conClave('otra-clave'))->get('/docs/api')->assertUnauthorized();
        $this->withHeaders($this->conClave('otra-clave'))->get('/docs/api.json')->assertUnauthorized();
    }

    public function test_un_usuario_incorrecto_no_entra_aunque_la_contrasena_sea_correcta(): void
    {
        config(['app.docs_password' => 'clave-de-prueba']);

        $this->withHeaders($this->conClave('clave-de-prueba', 'otro'))->get('/docs/api')->assertUnauthorized();
        $this->withHeaders($this->conClave('clave-de-prueba', ''))->get('/docs/api.json')->assertUnauthorized();
    }

    public function test_con_el_usuario_por_defecto_y_la_contrasena_correcta_entra(): void
    {
        config(['app.docs_password' => 'clave-de-prueba']);

        $this->withHeaders($this->conClave('clave-de-prueba'))->get('/docs/api')->assertOk();

        $this->withHeaders($this->conClave('clave-de-prueba'))->get('/docs/api.json')
            ->assertOk()
            ->assertJsonPath('info.title', config('app.name'));
    }

    public function test_el_usuario_se_puede_cambiar_con_docs_user(): void
    {
        config(['app.docs_password' => 'clave-de-prueba', 'app.docs_user' => 'revisor']);

        $this->withHeaders($this->conClave('clave-de-prueba', 'revisor'))->get('/docs/api')->assertOk();
        // el usuario por defecto deja de servir
        $this->withHeaders($this->conClave('clave-de-prueba', 'docs'))->get('/docs/api')->assertUnauthorized();
    }

    public function test_en_local_se_abre_sin_credenciales(): void
    {
        $this->app['env'] = 'local';
        config(['app.docs_password' => 'clave-de-prueba']);

        $this->get('/docs/api')->assertOk();
    }

    public function test_la_clave_de_la_documentacion_no_abre_las_rutas_protegidas(): void
    {
        config(['app.docs_password' => 'clave-de-prueba']);

        $this->withHeaders($this->conClave('clave-de-prueba'))->getJson('/api/competitions')->assertUnauthorized();
    }
}
