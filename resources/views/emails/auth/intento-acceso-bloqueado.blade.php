<p>Hola {{ $usuario->name }},</p>

<p>
    Se detectaron cinco intentos fallidos de acceso a su cuenta de HCEApp.
    Por seguridad, el ingreso fue bloqueado temporalmente durante {{ $minutosBloqueo }} minutos.
</p>

<p>
    IP registrada: {{ $ip }}
</p>

<p>
    Si usted realizo estos intentos, espere el tiempo indicado y vuelva a ingresar con su clave correcta.
    Si no reconoce esta actividad, informe al administrador del sistema.
</p>

<p>
    Este mensaje fue generado automaticamente por HCEApp.
</p>
