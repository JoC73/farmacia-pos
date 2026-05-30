# Manual de Usuario - Farmacia POS

## 1. Introduccion

Farmacia POS es un sistema de gestion para farmacias que permite administrar ventas, compras, productos, inventario, caja, sucursales, usuarios, reportes y funciones premium activables por un Super Usuario.

El sistema esta pensado para operar una farmacia con una o varias sucursales, manteniendo control sobre existencias, movimientos de inventario, ventas diarias, compras, usuarios y permisos.

Este manual explica el uso del sistema desde el punto de vista del usuario operativo, administrador y Super Usuario.

## 2. Acceso al Sistema

### 2.1 Iniciar sesion

1. Ingrese a la URL del sistema.
2. En la pantalla de login escriba su correo electronico.
3. Escriba su contrasena.
4. Presione el boton para ingresar.

Si las credenciales son correctas, el sistema lo enviara al dashboard principal.

### 2.2 Registro de usuarios

El sistema puede permitir registro desde la pantalla publica, pero en una operacion formal se recomienda que los usuarios sean creados por un Administrador desde el modulo Usuarios.

### 2.3 Cerrar sesion

1. Abra el menu lateral.
2. Busque la opcion Cerrar sesion.
3. Presione el boton para finalizar la sesion.

## 3. Roles del Sistema

El sistema usa roles y permisos para controlar que puede hacer cada usuario.

### 3.1 Administrador

Puede administrar la operacion general de la farmacia:

- Ver dashboard.
- Gestionar productos.
- Gestionar inventario.
- Crear ventas.
- Registrar compras.
- Administrar clientes.
- Administrar proveedores.
- Administrar caja.
- Ver reportes.
- Gestionar usuarios y roles, segun permisos configurados.

### 3.2 Cajero

Rol orientado al punto de venta:

- Crear ventas.
- Ver productos.
- Ver inventario.
- Abrir caja.
- Cerrar caja.
- Reimprimir tickets, si el permiso esta activo.

### 3.3 Encargado de Inventario

Rol orientado a control de productos y existencias:

- Ver productos.
- Crear y editar productos.
- Ver inventario.
- Registrar entradas de inventario.
- Revisar stock bajo.
- Revisar vencimientos.
- Registrar compras.

### 3.4 Super Usuario

Rol reservado para el propietario, proveedor del sistema o responsable autorizado.

El Super Usuario puede:

- Activar y desactivar modulos premium.
- Acceder al panel Modulos Premium.
- Acceder a funciones premium aunque esten bloqueadas para otros usuarios.
- Administrar roles y permisos protegidos.
- Controlar funciones sensibles del sistema.

Importante: un Administrador normal no debe poder crear ni modificar usuarios con rol Super Usuario.

## 4. Menu Principal

El menu lateral permite navegar por los modulos disponibles. Las opciones visibles pueden cambiar segun el rol del usuario y segun los modulos premium activados.

Modulos principales:

- Dashboard
- Ventas
- Clientes
- Productos
- Categorias
- Inventario
- Inventario Fisico
- Compras
- Proveedores
- Cajas
- Sucursales
- Usuarios
- Roles
- Reportes
- Modulos Premium, solo Super Usuario

## 5. Dashboard

El Dashboard muestra informacion general de la operacion.

Indicadores disponibles:

- Ventas de hoy.
- Ventas del mes.
- Compras del mes.
- Cajas abiertas.
- Productos mas vendidos.
- Productos con stock bajo.
- Productos proximos a vencer.
- Productos vencidos.

### Como usarlo

1. Ingrese al sistema.
2. Abra la opcion Dashboard.
3. Revise los indicadores principales.
4. Use la informacion para tomar decisiones de compras, ventas o reposicion.

## 6. Productos

El modulo Productos permite crear y administrar el catalogo de medicamentos o articulos.

### 6.1 Ver productos

1. Abra Productos.
2. El sistema mostrara el listado ordenado alfabeticamente.
3. Revise codigo, nombre, categoria, costo, precio, stock minimo, fecha de vencimiento y estado.

### 6.2 Crear producto

1. Abra Productos.
2. Presione Nuevo Producto.
3. Complete los datos:
   - Categoria
   - Codigo de barra
   - Nombre
   - Laboratorio
   - Costo
   - Precio de venta
   - Stock minimo
   - Fecha de vencimiento, si aplica
   - Descripcion
4. Presione Guardar.

### 6.3 Editar producto

1. Abra Productos.
2. Busque el producto.
3. Presione Editar.
4. Modifique los campos necesarios.
5. Guarde cambios.

### 6.4 Desactivar producto

1. Abra Productos.
2. Busque el producto.
3. Presione Desactivar.
4. Confirme la accion.

Recomendacion: desactivar es preferible a eliminar, porque conserva historial.

## 7. Categorias

Las categorias sirven para organizar los productos.

### Crear categoria

1. Abra Productos.
2. Presione Categorias.
3. Presione Nueva Categoria.
4. Ingrese nombre y descripcion.
5. Guarde.

### Editar categoria

1. Abra Categorias.
2. Presione Editar.
3. Modifique los datos.
4. Guarde.

## 8. Inventario

El inventario muestra las existencias por producto y sucursal.

### 8.1 Ver inventario

1. Abra Inventario.
2. El sistema mostrara:
   - Producto
   - Sucursal
   - Existencia
   - Stock minimo
   - Estado

Los productos pueden aparecer en varias sucursales, cada uno con su existencia correspondiente.

### 8.2 Nueva entrada de inventario

Use esta opcion para ingresar existencias manualmente.

1. Abra Inventario.
2. Presione Nueva Entrada.
3. Seleccione la sucursal.
4. Seleccione productos.
5. Ingrese cantidades.
6. Agregue observacion si aplica.
7. Guarde.

El sistema registra movimientos de inventario para dejar trazabilidad.

## 9. Inventario Fisico - Premium

El Inventario Fisico permite realizar conteos reales por sucursal mediante archivo Excel.

Este modulo es premium. Solo puede utilizarse cuando el Super Usuario active el modulo:

```text
Inventario fisico masivo
```

### 9.1 Cuando usarlo

Use este modulo cuando la farmacia haga conteo fisico de productos, por ejemplo:

- Inventario mensual.
- Auditoria interna.
- Cambio de encargado.
- Ajuste por diferencias.
- Revision completa por sucursal.

### 9.2 Descargar plantilla Excel

1. Abra Inventario.
2. Presione Inventario Fisico.
3. Seleccione la sucursal.
4. Presione Descargar Excel.
5. El sistema descargara un archivo con la data actual.

Columnas principales:

- producto_id
- codigo_barra
- producto
- categoria
- sucursal
- existencia_sistema
- existencia_fisica
- observacion

### 9.3 Llenar el conteo fisico

En Excel, el usuario debe llenar principalmente:

```text
existencia_fisica
observacion
```

No debe modificar:

- producto_id
- codigo_barra
- producto
- sucursal
- existencia_sistema

La columna existencia_fisica debe contener numeros enteros mayores o iguales a cero.

### 9.4 Subir archivo corregido

1. Abra Inventario Fisico.
2. Seleccione la misma sucursal.
3. Suba el archivo Excel corregido.
4. Presione Validar archivo.

El sistema mostrara una vista previa:

- Existencia en sistema.
- Existencia fisica.
- Diferencia.
- Observacion.

### 9.5 Confirmar ajustes

1. Revise la vista previa.
2. Confirme que las diferencias sean correctas.
3. Presione Confirmar ajustes.

El sistema aplicara solamente las diferencias y generara movimientos:

- AJUSTE_ENTRADA, si la existencia fisica es mayor.
- AJUSTE_SALIDA, si la existencia fisica es menor.

## 10. Ventas

El modulo Ventas permite registrar operaciones de venta.

### 10.1 Antes de vender

El usuario debe tener:

- Caja abierta.
- Sucursal asignada.
- Productos con existencia disponible.

### 10.2 Crear venta

1. Abra Ventas.
2. Presione Nueva Venta.
3. Seleccione cliente, si aplica.
4. Agregue productos.
5. Ingrese cantidades.
6. Revise total.
7. Confirme la venta.

El sistema descuenta inventario de la sucursal del usuario y registra movimiento de caja.

### 10.3 Ver detalle de venta

1. Abra Ventas.
2. Busque la venta.
3. Abra el detalle.
4. Revise cliente, productos, cantidades y total.

## 11. Clientes

Permite administrar clientes de la farmacia.

### Crear cliente

1. Abra Clientes.
2. Presione Nuevo Cliente.
3. Ingrese datos del cliente.
4. Guarde.

### Editar cliente

1. Abra Clientes.
2. Presione Editar.
3. Actualice la informacion.
4. Guarde.

## 12. Compras

El modulo Compras registra compras a proveedores y aumenta inventario.

### 12.1 Crear compra

1. Abra Compras.
2. Presione Nueva Compra.
3. Seleccione proveedor.
4. Agregue productos.
5. Ingrese cantidad y costo.
6. Guarde.

El sistema:

- Registra la compra.
- Crea detalle de compra.
- Aumenta inventario de la sucursal del usuario.
- Registra movimiento de inventario.

### 12.2 Ver detalle de compra

1. Abra Compras.
2. Seleccione una compra.
3. Revise proveedor, productos, cantidades, costos y total.

## 13. Proveedores

Permite administrar proveedores.

### Crear proveedor

1. Abra Proveedores.
2. Presione Nuevo Proveedor.
3. Ingrese nombre, telefono, direccion y datos disponibles.
4. Guarde.

### Editar proveedor

1. Abra Proveedores.
2. Presione Editar.
3. Modifique datos.
4. Guarde.

## 14. Cajas

El modulo Cajas controla apertura, cierre y movimientos.

### 14.1 Abrir caja

1. Abra Cajas.
2. Presione Apertura.
3. Ingrese monto inicial.
4. Guarde.

### 14.2 Cerrar caja

1. Abra Cajas.
2. Busque la caja abierta.
3. Presione Cierre.
4. Ingrese los datos solicitados.
5. Confirme.

### 14.3 Ver cajas

1. Abra Cajas.
2. Revise estado, fechas, usuario y montos.

## 15. Sucursales

El modulo Sucursales administra los puntos de operacion de la farmacia.

### 15.1 Ver sucursales

1. Abra Sucursales.
2. Revise nombre, direccion, telefono y estado.

### 15.2 Crear nueva sucursal - Premium

La creacion de nuevas sucursales es una funcion premium.

El modulo debe estar activo:

```text
Creacion de sucursales adicionales
```

Si no esta activo:

- El boton puede mostrar indicacion Premium.
- Al intentar ingresar, el sistema mostrara una pantalla de modulo bloqueado.

Si esta activo:

1. Abra Sucursales.
2. Presione Nueva Sucursal.
3. Ingrese nombre, direccion y telefono.
4. Guarde.

### 15.3 Editar sucursal

Editar sucursales existentes no requiere el modulo premium de creacion.

1. Abra Sucursales.
2. Presione Editar.
3. Modifique datos.
4. Guarde.

### 15.4 Desactivar sucursal

1. Abra Sucursales.
2. Presione Desactivar.
3. Confirme.

Recomendacion: no eliminar fisicamente sucursales porque pueden tener ventas, compras, usuarios e inventario historico.

## 16. Usuarios

Permite crear y administrar usuarios del sistema.

### 16.1 Crear usuario

1. Abra Usuarios.
2. Presione Nuevo Usuario.
3. Ingrese:
   - Nombre
   - Correo
   - Contrasena
   - Confirmacion de contrasena
   - Sucursal
   - Rol
4. Guarde.

### 16.2 Editar usuario

1. Abra Usuarios.
2. Presione Editar.
3. Modifique datos.
4. Si desea cambiar contrasena, ingrese una nueva.
5. Guarde.

### 16.3 Seguridad del Super Usuario

Un Administrador normal no puede:

- Crear usuarios con rol Super Usuario.
- Editar usuarios Super Usuario.
- Asignarse rol Super Usuario.

Estas acciones quedan reservadas al propio Super Usuario o a la configuracion inicial del sistema.

## 17. Roles y Permisos

Permite administrar permisos segun el rol.

### 17.1 Ver roles

1. Abra Roles.
2. Revise roles disponibles y permisos asignados.

### 17.2 Crear rol

1. Abra Roles.
2. Presione Nuevo Rol.
3. Ingrese nombre.
4. Seleccione permisos.
5. Guarde.

### 17.3 Editar permisos

1. Abra Roles.
2. Seleccione Editar permisos.
3. Marque o desmarque permisos.
4. Guarde.

### 17.4 Proteccion del rol Super Usuario

El rol Super Usuario no debe ser visible ni editable por administradores normales.

## 18. Reportes

El modulo Reportes permite consultar informacion operativa.

Reportes actuales:

- Reporte general.
- Reporte de ventas.

Dependiendo de permisos y modulos premium, pueden agregarse:

- Reportes avanzados.
- Reportes de ganancias.
- Reportes de inventario.
- Reportes de compras.
- Reportes de caja.
- Exportaciones.
- PDF profesional.

## 19. Modulos Premium

Los modulos premium son funciones avanzadas que pueden estar bloqueadas o activas.

Solo el Super Usuario puede acceder al panel:

```text
Modulos Premium
```

### 19.1 Como activar un modulo premium

1. Inicie sesion como Super Usuario.
2. Abra Modulos Premium.
3. Busque el modulo.
4. Presione Activar modulo.
5. Confirme la accion.

El modulo queda disponible para los usuarios con permisos correspondientes.

### 19.2 Como desactivar un modulo premium

1. Inicie sesion como Super Usuario.
2. Abra Modulos Premium.
3. Busque el modulo activo.
4. Presione Desactivar modulo.
5. Confirme.

El modulo vuelve a quedar bloqueado para usuarios normales.

### 19.3 Modulos premium disponibles

#### Creacion de sucursales adicionales

Permite crear nuevas sucursales.

Uso recomendado:

- Cuando el cliente paga por expansion.
- Cuando se habilita una nueva sede.
- Cuando el proveedor autoriza crecimiento de la instalacion.

#### Inventario fisico masivo

Permite descarga y carga Excel para conteos fisicos.

Uso recomendado:

- Auditorias.
- Inventarios mensuales.
- Ajustes masivos por sucursal.

#### Reportes avanzados y PDF profesional

Pensado para reportes mas completos, documentos profesionales y exportaciones.

#### Dashboard con graficas avanzadas

Pensado para indicadores visuales, comparativos, tendencias y analisis gerencial.

#### Transferencias entre sucursales

Permitira trasladar productos entre sucursales con movimientos de salida y entrada.

#### Devoluciones

Permitira devoluciones de clientes y proveedores, afectando inventario, ventas o compras segun corresponda.

#### Credito a clientes

Permitira ventas al credito, control de saldos, pagos y cuentas por cobrar.

#### Impresion termica avanzada

Permitira tickets optimizados para impresoras termicas y configuraciones especiales.

#### Codigo de barras y lector

Permitira flujos optimizados para lectura de codigo de barras.

#### Lotes, vencimientos y FEFO

Permitira administrar existencias por lote y salida por vencimiento mas proximo.

#### IA para inventario

Permitira analisis inteligente, alertas predictivas y sugerencias de compra.

#### API movil

Permitira conectar apps moviles o integraciones externas.

#### App para vendedores

Permitira uso movil por vendedores, rutas o equipos comerciales.

#### Compras inteligentes automaticas

Permitira sugerencias automaticas de compra segun rotacion, stock y proveedores.

## 20. Buenas Practicas Operativas

### 20.1 Usuarios

- Cada persona debe tener su propio usuario.
- No compartir contrasenas.
- Desactivar usuarios que ya no trabajen en la farmacia.

### 20.2 Productos

- Mantener codigos de barra correctos.
- Usar nombres claros y consistentes.
- Revisar precios antes de vender.

### 20.3 Inventario

- Registrar entradas correctamente.
- Hacer inventario fisico periodicamente.
- Revisar stock bajo.
- Revisar vencimientos.

### 20.4 Caja

- Abrir caja antes de vender.
- Cerrar caja al finalizar turno.
- Revisar diferencias.

### 20.5 Sucursales

- No eliminar sucursales con historial.
- Desactivar sucursales que ya no operen.
- Crear nuevas sucursales solo con autorizacion premium.

## 21. Errores Comunes

### Credenciales incorrectas

Verifique correo y contrasena. Si el usuario fue creado en otra instalacion o base de datos, no funcionara en esta.

### No aparece una opcion del menu

Puede deberse a:

- Falta de permisos.
- Rol incorrecto.
- Modulo premium bloqueado.

### No puedo vender

Revise:

- Caja abierta.
- Usuario con sucursal asignada.
- Existencia suficiente.
- Permiso de ventas.

### No puedo crear sucursal

La creacion de sucursales es premium. Debe activarla el Super Usuario.

### No puedo usar Inventario Fisico

El modulo Inventario Fisico es premium. Debe estar activo y el usuario debe tener permiso de ajuste de inventario.

## 22. Recomendaciones Para el Administrador

- Revisar usuarios y roles periodicamente.
- No asignar permisos excesivos.
- Mantener productos actualizados.
- Realizar cierres de caja diarios.
- Usar inventario fisico para auditorias.
- Activar modulos premium solo cuando correspondan al plan contratado.

## 23. Recomendaciones Para el Super Usuario

- Mantener su cuenta protegida.
- No compartir credenciales.
- Activar solo modulos contratados o autorizados.
- Revisar el impacto antes de desactivar modulos en uso.
- Controlar la creacion de nuevas sucursales.
- Usar el panel premium como centro de licenciamiento operativo.

## 24. Soporte

Si ocurre un error, se recomienda anotar:

- Usuario afectado.
- Modulo usado.
- Accion realizada.
- Mensaje mostrado.
- Fecha y hora aproximada.

Con esta informacion el soporte tecnico podra revisar logs y corregir el problema con mayor rapidez.

