<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfiguracionLoginRequest;
use App\Models\ConfiguracionSistema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ConfiguracionLoginController extends Controller
{
    public function editar(): View
    {
        return view('configuracion.login', [
            'configuracion' => ConfiguracionSistema::actual(),
        ]);
    }

    public function actualizar(ConfiguracionLoginRequest $request): RedirectResponse
    {
        $configuracion = ConfiguracionSistema::query()->firstOrNew(['id' => 1]);

        $datos = $request->safe()->except(['logo', 'login_background']);
        $datos['actualizado_por'] = $request->user()->id;
        $datos['registrado_por'] ??= $configuracion->registrado_por ?: $request->user()->id;

        if ($request->hasFile('logo')) {
            $datos['logo_path'] = $this->guardarImagenPublica($request->file('logo'), 'logo');
        }

        if ($request->hasFile('login_background')) {
            $datos['login_background_path'] = $this->guardarImagenPublica($request->file('login_background'), 'login-background');
        }

        $configuracion->fill($datos);
        $configuracion->save();

        flash()->success('Configuracion visual del login actualizada.', [], 'Configuracion');

        return redirect()->route('configuracion.login.editar');
    }

    private function guardarImagenPublica($archivo, string $prefijo): string
    {
        $directorio = public_path('uploads/configuracion');
        File::ensureDirectoryExists($directorio);

        $nombre = $prefijo.'-'.now()->format('YmdHis').'-'.Str::random(8).'.'.$archivo->getClientOriginalExtension();
        $archivo->move($directorio, $nombre);

        return '/uploads/configuracion/'.$nombre;
    }
}
