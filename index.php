<?php
include 'conexion.php';

mysqli_set_charset($conexion, 'utf8mb4');
$flash = consume_flash_message();

$equiposQuery = mysqli_query($conexion, "SELECT * FROM equipos");
$equipos = array();

if ($equiposQuery) {
    while ($fila = mysqli_fetch_assoc($equiposQuery)) {
        $equipos[] = $fila;
    }
}

usort($equipos, function ($a, $b) {
    return strnatcasecmp((string) $a['numero_caja'], (string) $b['numero_caja']);
});

$total = count($equipos);
$disponibles = count(array_filter($equipos, function ($equipo) {
    return isset($equipo['estado']) && $equipo['estado'] === 'Disponible';
}));
$asignados = count(array_filter($equipos, function ($equipo) {
    return isset($equipo['estado']) && $equipo['estado'] === 'Asignado';
}));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Inventario de Equipos Nuevos</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body class="app-body">
    <div class="bg-shape bg-shape-one"></div>
    <div class="bg-shape bg-shape-two"></div>

    <main class="page-shell">
        <section class="hero-panel">
            <div class="hero-copy">
                <span class="eyebrow">Panel de inventario</span>
                <h1>Inventario de equipos nuevos</h1>
                
            </div>

            <div class="hero-highlight">
                <span class="hero-highlight-label">Estado general de equipos</span>
                <strong><?php echo $total; ?> equipos registrados</strong>
                <p>Consulta, edita y filtra la informacion sin salir de la misma pantalla.</p>
            </div>
        </section>

        <section class="stats-grid" aria-label="Resumen del inventario">
            <article class="stat-card stat-card-available">
                <span class="stat-label">Disponibles</span>
                <strong class="stat-value"><?php echo $disponibles; ?></strong>
                <p>Listos para asignacion.</p>
            </article>

            <article class="stat-card stat-card-assigned">
                <span class="stat-label">Asignados</span>
                <strong class="stat-value"><?php echo $asignados; ?></strong>
                <p>Equipos que en este momento aparecen como asignados.</p>
            </article>
        </section>

        <?php if ($flash) { ?>
            <div class="flash-banner flash-<?php echo e($flash['type']); ?>">
                <?php echo e($flash['message']); ?>
            </div>
        <?php } ?>

        <section class="content-grid">
            <article class="panel-card">
                <div class="section-heading">
                    <div>
                        <span class="section-tag">Registro</span>
                        <h2>Agregar Nuevo Equipo</h2>
                    </div>
                    <p>Completa la informacion principal para guardar el equipo en el inventario.</p>
                </div>

                <form action="guardar.php" method="POST" id="formularioEquipo" class="inventory-form">
                    <?php echo csrf_input(); ?>
                    <div class="form-grid">
                        <label class="field">
                            <span>Numero de caja</span>
                            <input type="text" name="numero_caja" placeholder="Ej: CAJA-001" required>
                        </label>

                        <label class="field">
                            <span>Marca del PC</span>
                            <input type="text" name="marca_pc" placeholder="Ej: Dell" required>
                        </label>

                        <label class="field">
                            <span>Nombre del PC</span>
                            <input type="text" name="nombre_pc" placeholder="Ej: Laptop-oficina-01" required>
                        </label>

                        <label class="field">
                            <span>Modelo del PC</span>
                            <input type="text" name="modelo_pc" placeholder="Ej: Latitude 5440" required>
                        </label>

                        <label class="field">
                            <span>Serial del PC</span>
                            <input type="text" name="serial_pc" placeholder="Ej: PC-45892" required>
                        </label>

                        <label class="field">
                            <span>Modelo del cargador</span>
                            <input type="text" name="modelo_cargador" placeholder="Ej: Dell 65W" required>
                        </label>

                        <label class="field">
                            <span>Serial del cargador</span>
                            <input type="text" name="serial_cargador" placeholder="Ej: CAR-99812" required>
                        </label>

                        <label class="field">
                            <span>Estado</span>
                            <select name="estado" required>
                                <option value="Disponible" selected>Disponible</option>
                                <option value="Asignado">Asignado</option>
                            </select>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Guardar equipo</button>
                        <button type="reset" class="btn btn-secondary">Limpiar formulario</button>
                    </div>
                </form>
            </article>

            <article class="panel-card panel-card-wide">
                <div class="section-heading">
                    <div>
                        <span class="section-tag">Consulta</span>
                        <h2>Equipos registrados</h2>
                    </div>
                    <p>Busca por cualquier dato o filtra rapidamente por estado.</p>
                </div>

                <?php if ($total > 0) { ?>
                    <div class="toolbar">
                        <label class="field field-compact">
                            <span>Busqueda rapida</span>
                            <input type="search" id="busqueda" placeholder="Buscar por caja, nombre, serial, marca o modelo">
                        </label>

                        <label class="field field-compact">
                            <span>Filtrar por estado</span>
                            <select id="filtroEstado">
                                <option value="">Todos</option>
                                <option value="Disponible">Disponible</option>
                                <option value="Asignado">Asignado</option>
                            </select>
                        </label>

                        <button type="button" class="btn btn-secondary btn-inline" id="btnExportarExcel">Descargar Excel</button>
                        <span class="export-note" id="estadoExportacion" aria-live="polite">Se descargara un archivo Excel organizado y listo para revisar.</span>
                    </div>

                    <div class="table-meta">
                        <p id="contadorRegistros">Mostrando <?php echo $total; ?> equipos</p>
                    </div>

                    <p class="table-hint">Desliza la barra horizontal para ver toda la informacion de la tabla.</p>

                    <div class="table-shell">
                        <table class="inventory-table">
                            <thead>
                                <tr>
                                    <th>Caja</th>
                                    <th>Marca</th>
                                    <th>Nombre PC</th>
                                    <th>Modelo PC</th>
                                    <th>Serial PC</th>
                                    <th>Modelo cargador</th>
                                    <th>Serial cargador</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipos as $fila) {
                                    $estadoFila = $fila['estado'] === 'Disponible' ? 'Disponible' : 'Asignado';
                                    $searchData = implode(' ', array(
                                        $fila['numero_caja'],
                                        $fila['marca_pc'],
                                        isset($fila['nombre_pc']) ? $fila['nombre_pc'] : '',
                                        $fila['modelo_pc'],
                                        $fila['serial_pc'],
                                        $fila['modelo_cargador'],
                                        $fila['serial_cargador']
                                    ));
                                ?>
                                    <tr
                                        data-equipo-row
                                        data-estado="<?php echo e($estadoFila); ?>"
                                        data-search="<?php echo e($searchData); ?>"
                                    >
                                        <td data-label="Caja"><?php echo e($fila['numero_caja']); ?></td>
                                        <td data-label="Marca"><?php echo e($fila['marca_pc']); ?></td>
                                        <td data-label="Nombre PC"><?php echo e(isset($fila['nombre_pc']) ? $fila['nombre_pc'] : ''); ?></td>
                                        <td data-label="Modelo PC"><?php echo e($fila['modelo_pc']); ?></td>
                                        <td data-label="Serial PC"><?php echo e($fila['serial_pc']); ?></td>
                                        <td data-label="Modelo cargador"><?php echo e($fila['modelo_cargador']); ?></td>
                                        <td data-label="Serial cargador"><?php echo e($fila['serial_cargador']); ?></td>
                                        <td data-label="Estado">
                                            <span class="status-chip <?php echo $estadoFila === 'Disponible' ? 'status-available' : 'status-assigned'; ?>">
                                                <?php echo e($estadoFila); ?>
                                            </span>
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="action-group">
                                                <a class="btn btn-table btn-edit" href="editar.php?id=<?php echo (int) $fila['id']; ?>">Editar</a>
                                                <form action="eliminar.php" method="POST" class="action-form" data-delete-form>
                                                    <input type="hidden" name="id" value="<?php echo (int) $fila['id']; ?>">
                                                    <?php echo csrf_input(); ?>
                                                    <button type="submit" class="btn btn-table btn-delete">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="empty-filter-state is-hidden" id="sinResultados">
                        No hay equipos que coincidan con la busqueda o el filtro actual.
                    </div>
                <?php } else { ?>
                    <div class="empty-state">
                        <h3>Aun no hay equipos registrados</h3>
                        <p>Usa el formulario para cargar el primer equipo y comenzar a organizar el inventario.</p>
                    </div>
                <?php } ?>
            </article>
        </section>
    </main>

    <script nonce="<?php echo e(csp_nonce()); ?>">
        const busqueda = document.getElementById('busqueda');
        const filtroEstado = document.getElementById('filtroEstado');
        const filas = Array.from(document.querySelectorAll('[data-equipo-row]'));
        const contadorRegistros = document.getElementById('contadorRegistros');
        const sinResultados = document.getElementById('sinResultados');
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const exportButton = document.getElementById('btnExportarExcel');
        const formulariosEliminar = Array.from(document.querySelectorAll('[data-delete-form]'));

        function confirmarEliminacion() {
            return confirm('Desea eliminar este equipo?');
        }

        async function exportTableToExcel() {
            const table = document.querySelector('.inventory-table');
            const exportStatus = document.getElementById('estadoExportacion');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';

            if (!table) {
                alert('No hay tabla disponible para exportar.');
                return;
            }

            const rows = Array.from(table.querySelectorAll('tbody tr')).filter(row => !row.classList.contains('is-hidden'));
            if (!rows.length) {
                alert('No hay datos visibles para exportar.');
                return;
            }

            const headers = Array.from(table.querySelectorAll('thead th:not(:last-child)')).map(th => th.textContent.trim());
            const dataRows = rows.map(row => Array.from(row.querySelectorAll('td:not(:last-child)')).map(td => td.textContent.trim()));
            const payload = { headers: headers, rows: dataRows, title: 'Inventario de equipos' };
            const defaultStatus = exportStatus ? (exportStatus.dataset.defaultText || exportStatus.textContent) : '';

            if (exportStatus && !exportStatus.dataset.defaultText) {
                exportStatus.dataset.defaultText = defaultStatus;
            }

            if (exportButton) {
                exportButton.disabled = true;
            }

            if (exportStatus) {
                exportStatus.textContent = 'Preparando archivo Excel...';
                exportStatus.classList.remove('export-note-success', 'export-note-error');
                exportStatus.classList.add('export-note-loading');
            }

            try {
                const response = await fetch('export_excel.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams({
                        data: JSON.stringify(payload),
                        csrf_token: csrfToken
                    })
                });

                if (!response.ok) {
                    const errorText = (await response.text()).trim();
                    throw new Error(errorText || 'No fue posible generar el archivo Excel.');
                }

                const blob = await response.blob();
                const disposition = response.headers.get('Content-Disposition') || '';
                const filenameMatch = disposition.match(/filename="([^"]+)"/i);
                const filename = filenameMatch ? filenameMatch[1] : 'inventario-equipos.xls';
                const downloadUrl = window.URL.createObjectURL(blob);
                const link = document.createElement('a');

                link.href = downloadUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();

                window.setTimeout(() => {
                    window.URL.revokeObjectURL(downloadUrl);
                }, 1500);

                if (exportStatus) {
                    exportStatus.textContent = 'Excel descargado correctamente.';
                    exportStatus.classList.remove('export-note-loading', 'export-note-error');
                    exportStatus.classList.add('export-note-success');
                }
            } catch (error) {
                const message = error instanceof Error ? error.message : 'No fue posible descargar el archivo Excel.';

                if (exportStatus) {
                    exportStatus.textContent = message;
                    exportStatus.classList.remove('export-note-loading', 'export-note-success');
                    exportStatus.classList.add('export-note-error');
                }

                alert(message);
            } finally {
                if (exportButton) {
                    exportButton.disabled = false;
                }

                if (exportStatus) {
                    window.setTimeout(() => {
                        exportStatus.textContent = exportStatus.dataset.defaultText || 'Se descargara un archivo Excel organizado y listo para revisar.';
                        exportStatus.classList.remove('export-note-loading', 'export-note-success', 'export-note-error');
                    }, 4000);
                }
            }
        }

        function normalizarBusqueda(valor) {
            return valor
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]/g, '');
        }

        function actualizarTabla() {
            if (!filas.length || !busqueda || !filtroEstado || !contadorRegistros) {
                return;
            }

            const termino = normalizarBusqueda(busqueda.value.trim());
            const estadoSeleccionado = filtroEstado.value;
            let visibles = 0;

            filas.forEach((fila) => {
                const searchValue = fila.dataset.searchNormalizado || normalizarBusqueda(fila.dataset.search || '');
                fila.dataset.searchNormalizado = searchValue;

                const coincideTexto = termino === '' || searchValue.includes(termino);
                const coincideEstado = estadoSeleccionado === '' || fila.dataset.estado === estadoSeleccionado;
                const mostrar = coincideTexto && coincideEstado;

                fila.classList.toggle('is-hidden', !mostrar);

                if (mostrar) {
                    visibles += 1;
                }
            });

            contadorRegistros.textContent = visibles === 1
                ? 'Mostrando 1 equipo'
                : `Mostrando ${visibles} equipos`;

            if (sinResultados) {
                sinResultados.classList.toggle('is-hidden', visibles !== 0);
            }
        }

        if (busqueda && filtroEstado) {
            busqueda.addEventListener('input', actualizarTabla);
            filtroEstado.addEventListener('change', actualizarTabla);
        }

        if (exportButton) {
            exportButton.addEventListener('click', exportTableToExcel);
        }

        formulariosEliminar.forEach((formulario) => {
            formulario.addEventListener('submit', (event) => {
                if (!confirmarEliminacion()) {
                    event.preventDefault();
                }
            });
        });

    </script>
</body>
</html>
