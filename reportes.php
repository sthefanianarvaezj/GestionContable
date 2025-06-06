<?php
session_start();
require_once 'config/database.php';

// Clase para la pantalla de reportes
class ModuloReportes
{
    public function mostrar()
    {
        global $titulo, $pagina_actual;
        $titulo = 'Módulo de Reportes';
        $pagina_actual = 'reportes';

        ob_start();
        ?>
        <div class="container mt-4">
            <h1 class="mb-4">Reportes del Sistema</h1>
            <div class="row">
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-file-earmark-spreadsheet"></i> Usuarios</h5>
                            <p class="card-text">Descarga un reporte de todos los usuarios registrados.</p>
                            <a href="reporte_usuarios.php" class="btn btn-primary">
                                <i class="bi bi-download"></i> Descargar Excel
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Puedes agregar más tarjetas para otros reportes -->
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-file-earmark-spreadsheet"></i> Trabajos Contables</h5>
                            <p class="card-text">Descarga un reporte de los trabajos contables.</p>
                            <a href="trabajos_excel.php" class="btn btn-primary">
                                <i class="bi bi-download"></i> Descargar Excel
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Más tarjetas según tus necesidades -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Instanciar y mostrar el módulo
$modulo = new ModuloReportes();
$contenido = $modulo->mostrar();
require_once 'layout.php';