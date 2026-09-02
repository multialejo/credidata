<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   version="1.0.0",
 *   title="CrediData API",
 *   description="API para consultas de identidad y administración de créditos. Los ejemplos usan datos ficticios.",
 *
 *   @OA\Contact(name="Equipo CrediData")
 * )
 *
 * @OA\Server(url="/", description="Servidor actual")
 *
 * @OA\Tag(name="Consultas", description="Consultas cobrables con API Key")
 * @OA\Tag(name="API Key", description="Administración de la API Key autenticada")
 * @OA\Tag(name="Sesión", description="Usuario autenticado")
 * @OA\Tag(name="Recargas PayPal", description="Recargas de cliente con PayPal")
 * @OA\Tag(name="Recargas Payphone", description="Recargas de cliente con Payphone")
 * @OA\Tag(name="Administración", description="Operaciones para staff")
 *
 * @OA\Schema(schema="Error", type="object", required={"tipo"}, @OA\Property(property="tipo", type="string", example="VALIDACION"), @OA\Property(property="detalle", type="string", nullable=true, example="El campo monto_usd es obligatorio."))
 * @OA\Schema(schema="Metadatos", type="object", @OA\Property(property="timestamp", type="string", format="date-time", example="2026-09-02T16:00:00+00:00"), @OA\Property(property="creditos_gastados", type="integer", example=1), @OA\Property(property="creditos_restantes", type="integer", example=24), @OA\Property(property="fuente", type="string", example="dinardap"))
 * @OA\Schema(schema="Respuesta", type="object", required={"codigo","exito","mensaje"}, @OA\Property(property="codigo", type="integer", example=200), @OA\Property(property="exito", type="boolean", example=true), @OA\Property(property="mensaje", type="string", example="Operación exitosa"), @OA\Property(property="datos", type="object", nullable=true), @OA\Property(property="error", ref="#/components/schemas/Error"), @OA\Property(property="metadatos", ref="#/components/schemas/Metadatos"))
 * @OA\Schema(schema="Usuario", type="object", @OA\Property(property="id", type="integer", example=7), @OA\Property(property="uid", type="string", format="uuid"), @OA\Property(property="nombre", type="string", example="Usuario de prueba"), @OA\Property(property="email", type="string", format="email", example="cliente@example.test"), @OA\Property(property="roles", type="array", @OA\Items(type="string", example="cliente")), @OA\Property(property="tipo_acceso", type="string", example="cliente"))
 * @OA\Schema(schema="ConsultaCedula", type="object", @OA\Property(property="cedula", type="string", example="0912345678"), @OA\Property(property="nombres", type="string", nullable=true, example="Persona Ficticia"), @OA\Property(property="profesion", type="string", nullable=true), @OA\Property(property="fechaNacimiento", type="string", nullable=true), @OA\Property(property="ubicacion", type="object", @OA\Property(property="provincia", type="string", nullable=true), @OA\Property(property="canton", type="string", nullable=true), @OA\Property(property="parroquia", type="string", nullable=true)), @OA\Property(property="ruc", type="string", nullable=true))
 * @OA\Schema(schema="Establecimiento", type="object", additionalProperties=true, description="Los campos provienen del Catastro SRI y pueden variar según la fuente.")
 * @OA\Schema(schema="Recarga", type="object", @OA\Property(property="id", type="integer", example=12), @OA\Property(property="estado", type="string", enum={"pendiente","completada","fallida","rechazada"}, example="pendiente"), @OA\Property(property="creditos_obtenidos", type="integer", example=100), @OA\Property(property="monto_usd", type="number", format="float", example=10), @OA\Property(property="referencia_externa", type="string", example="MOCK-ORDER-123"))
 */
class OpenApi
{
    /**
     * @OA\Post(
     *   path="/api/v1/consulta/cedula", tags={"Consultas"}, summary="Consulta una cédula",
     *   description="Requiere API Key Bearer activa, IP permitida y scope consulta:cedula (o wildcard aplicable). Las respuestas 404 consumen créditos.", security={{"ApiKeyBearer":{}}},
     *
     *   @OA\RequestBody(required=true, @OA\JsonContent(required={"cedula"}, @OA\Property(property="cedula", type="string", example="0912345678", description="Identificador ecuatoriano válido."))),
     *
     *   @OA\Response(response=200, description="Consulta exitosa", @OA\JsonContent(allOf={@OA\Schema(ref="#/components/schemas/Respuesta"), @OA\Schema(@OA\Property(property="datos", ref="#/components/schemas/ConsultaCedula"))})),
     *   @OA\Response(response=401, description="API Key inválida, revocada, sin scope o IP no permitida", @OA\JsonContent(ref="#/components/schemas/Respuesta")),
     *   @OA\Response(response=402, description="Saldo insuficiente", @OA\JsonContent(ref="#/components/schemas/Respuesta")),
     *   @OA\Response(response=404, description="No se encontraron datos; es una consulta cobrable", @OA\JsonContent(ref="#/components/schemas/Respuesta")),
     *   @OA\Response(response=422, description="Cédula inválida"), @OA\Response(response=503, description="Dinardap no disponible", @OA\JsonContent(ref="#/components/schemas/Respuesta"))
     * )
     */
    public function consultaCedula(): void {}

    /**
     * @OA\Post(
     *   path="/api/v1/consulta/ruc", tags={"Consultas"}, summary="Consulta establecimientos por RUC",
     *   description="Requiere API Key Bearer activa, IP permitida y scope consulta:ruc (o wildcard aplicable). Un 404 sin establecimientos es exitoso y cobrable.", security={{"ApiKeyBearer":{}}},
     *
     *   @OA\RequestBody(required=true, @OA\JsonContent(required={"ruc"}, @OA\Property(property="ruc", type="string", minLength=13, maxLength=13, pattern="^[0-9]+$", example="0991234567001"))),
     *
     *   @OA\Response(response=200, description="Establecimientos encontrados", @OA\JsonContent(ref="#/components/schemas/Respuesta")), @OA\Response(response=401, description="API Key no autorizada"), @OA\Response(response=402, description="Saldo insuficiente"),
     *   @OA\Response(response=404, description="No se encontraron establecimientos; datos contiene ruc y establecimientos vacío", @OA\JsonContent(ref="#/components/schemas/Respuesta")), @OA\Response(response=422, description="RUC inválido"), @OA\Response(response=503, description="Catastro SRI no disponible")
     * )
     */
    public function consultaRuc(): void {}

    /**
     * @OA\Post(path="/api/v1/api-key/revocar", tags={"API Key"}, summary="Revoca la API Key actual", description="No recibe cuerpo. La key revocada deja de poder autenticarse.", security={{"ApiKeyBearer":{}}}, @OA\Response(response=200, description="API Key revocada", @OA\JsonContent(ref="#/components/schemas/Respuesta")), @OA\Response(response=401, description="API Key inválida o revocada"))
     */
    public function revocarApiKey(): void {}

    /**
     * @OA\Post(path="/api/v1/api-key/rotar", tags={"API Key"}, summary="Rota la API Key actual", description="Los campos omitidos conservan su valor actual. api_key solo se entrega en esta respuesta y no puede recuperarse.", security={{"ApiKeyBearer":{}}}, @OA\RequestBody(@OA\JsonContent(@OA\Property(property="alias", type="string", maxLength=100, nullable=true, example="Integración ERP"), @OA\Property(property="scopes", type="array", @OA\Items(type="string", enum={"consulta:cedula","consulta:ruc","*"})), @OA\Property(property="ips", type="array", @OA\Items(type="string", format="ipv4", example="203.0.113.10")))), @OA\Response(response=200, description="Nueva key entregada una sola vez", @OA\JsonContent(ref="#/components/schemas/Respuesta")), @OA\Response(response=401, description="API Key no autorizada"), @OA\Response(response=422, description="Alias, scopes o IPs inválidos"))
     */
    public function rotarApiKey(): void {}

    /**
     * @OA\Get(path="/api/user", tags={"Sesión"}, summary="Obtiene el usuario Sanctum autenticado", security={{"SanctumBearer":{}}}, @OA\Response(response=200, description="Usuario autenticado", @OA\JsonContent(ref="#/components/schemas/Usuario")), @OA\Response(response=401, description="Token Sanctum ausente o inválido"))
     */
    public function usuario(): void {}

    /**
     * @OA\Post(path="/api/v1/recargas/paypal/orden", tags={"Recargas PayPal"}, summary="Crea una orden PayPal", description="Requiere token Sanctum de cliente. El mínimo y la tasa de créditos se configuran en el servidor.", security={{"SanctumBearer":{}}}, @OA\RequestBody(required=true, @OA\JsonContent(required={"monto_usd"}, @OA\Property(property="monto_usd", type="number", format="float", minimum=0.01, example=10))), @OA\Response(response=200, description="Orden creada con order_id, approval_url y créditos calculados", @OA\JsonContent(ref="#/components/schemas/Respuesta")), @OA\Response(response=401, description="Token inválido"), @OA\Response(response=422, description="Monto inválido o menor al mínimo"), @OA\Response(response=503, description="PayPal no disponible"))
     */
    public function crearOrdenPaypal(): void {}

    /**
     * @OA\Post(path="/api/v1/recargas/paypal/{order_id}/capturar", tags={"Recargas PayPal"}, summary="Captura una orden PayPal", description="Solo el cliente propietario puede capturarla. Repetir una captura completada es idempotente. Las órdenes fallidas o rechazadas devuelven 409.", security={{"SanctumBearer":{}}}, @OA\Parameter(name="order_id", in="path", required=true, @OA\Schema(type="string", example="MOCK-ORDER-123")), @OA\Response(response=200, description="Recarga acreditada, ya procesada, o pago no completado (exito=false)"), @OA\Response(response=401, description="Token inválido"), @OA\Response(response=403, description="Orden de otro cliente"), @OA\Response(response=404, description="Orden no encontrada"), @OA\Response(response=409, description="Estado terminal"), @OA\Response(response=503, description="PayPal no disponible"))
     */
    public function capturarPaypal(): void {}

    /**
     * @OA\Post(path="/api/v1/recargas/payphone/transaccion", tags={"Recargas Payphone"}, summary="Crea una transacción Payphone", security={{"SanctumBearer":{}}}, @OA\RequestBody(required=true, @OA\JsonContent(required={"monto_usd"}, @OA\Property(property="monto_usd", type="number", format="float", minimum=0.01, example=10))), @OA\Response(response=200, description="Transacción creada con recarga_id, client_transaction_id y URLs de pago", @OA\JsonContent(ref="#/components/schemas/Respuesta")), @OA\Response(response=401, description="Token inválido"), @OA\Response(response=422, description="Monto inválido"), @OA\Response(response=503, description="Payphone no disponible"))
     */
    public function crearTransaccionPayphone(): void {}

    /**
     * @OA\Post(path="/api/v1/recargas/payphone/{id}/confirmar", tags={"Recargas Payphone"}, summary="Confirma una transacción Payphone", description="Solo el cliente propietario puede confirmarla. Repetir una recarga completada es idempotente.", security={{"SanctumBearer":{}}}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=12)), @OA\RequestBody(required=true, @OA\JsonContent(required={"clientTransactionId"}, @OA\Property(property="clientTransactionId", type="string", maxLength=64, example="bs-ficticia-001"))), @OA\Response(response=200, description="Recarga acreditada, ya procesada, o pago no completado (exito=false)"), @OA\Response(response=401, description="Token inválido"), @OA\Response(response=403, description="Transacción de otro cliente"), @OA\Response(response=404, description="Recarga no encontrada"), @OA\Response(response=409, description="Estado terminal"), @OA\Response(response=422, description="clientTransactionId inválido"), @OA\Response(response=503, description="Payphone no disponible"))
     */
    public function confirmarPayphone(): void {}

    /**
     * @OA\Get(path="/api/v1/admin/recargas/pendientes", tags={"Administración"}, summary="Lista recargas pendientes", description="Requiere token Sanctum de staff. Devuelve 15 elementos por página.", security={{"SanctumBearer":{}}}, @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", minimum=1, example=1)), @OA\Response(response=200, description="Recargas y paginación", @OA\JsonContent(ref="#/components/schemas/Respuesta")), @OA\Response(response=401, description="Token inválido"), @OA\Response(response=403, description="Usuario sin rol staff"))
     */
    public function pendientes(): void {}

    /**
     * @OA\Get(path="/api/v1/admin/recargas/{recarga}/comprobante", tags={"Administración"}, summary="Descarga el comprobante de una transferencia", description="Requiere token Sanctum de staff. Solo hay comprobantes para recargas por transferencia con archivo disponible.", security={{"SanctumBearer":{}}}, @OA\Parameter(name="recarga", in="path", required=true, @OA\Schema(type="integer", example=12)), @OA\Response(response=200, description="Archivo de comprobante", @OA\MediaType(mediaType="application/octet-stream", @OA\Schema(type="string", format="binary"))), @OA\Response(response=401, description="Token inválido"), @OA\Response(response=403, description="Usuario sin rol staff"), @OA\Response(response=404, description="Recarga, comprobante o archivo no disponible"))
     */
    public function comprobante(): void {}

    /**
     * @OA\Post(path="/api/v1/admin/recargas/{recarga}/rechazar", tags={"Administración"}, summary="Rechaza una recarga de pasarela", description="Requiere token Sanctum de staff. Solo PayPal y Payphone en estado que admita la transición.", security={{"SanctumBearer":{}}}, @OA\Parameter(name="recarga", in="path", required=true, @OA\Schema(type="integer", example=12)), @OA\RequestBody(required=true, @OA\JsonContent(required={"motivo"}, @OA\Property(property="motivo", type="string", minLength=10, maxLength=500, example="Comprobante de pago inconsistente."))), @OA\Response(response=200, description="Recarga rechazada", @OA\JsonContent(ref="#/components/schemas/Respuesta")), @OA\Response(response=401, description="Token inválido"), @OA\Response(response=403, description="Usuario sin rol staff"), @OA\Response(response=404, description="Recarga no encontrada"), @OA\Response(response=409, description="Método no autorizado o transición inválida"), @OA\Response(response=422, description="Motivo inválido"))
     */
    public function rechazar(): void {}

    /**
     * @OA\Post(path="/api/v1/admin/recargas/acreditar", tags={"Administración"}, summary="Acredita una transferencia manual", description="Requiere token Sanctum con rol admin. La referencia bancaria hace la operación idempotente para el mismo cliente.", security={{"SanctumBearer":{}}}, @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(required={"cliente_email","monto_usd","referencia_bancaria","motivo","comprobante"}, @OA\Property(property="cliente_email", type="string", format="email", example="cliente@example.test"), @OA\Property(property="monto_usd", type="number", format="float", minimum=0.01, example=10), @OA\Property(property="referencia_bancaria", type="string", maxLength=100, example="TRX-FICTICIA-001"), @OA\Property(property="motivo", type="string", minLength=10, maxLength=500, example="Transferencia verificada manualmente."), @OA\Property(property="comprobante", type="string", format="binary", description="JPG, JPEG, PNG o PDF; máximo 10 MB.")))), @OA\Response(response=200, description="Recarga acreditada o resultado idempotente", @OA\JsonContent(ref="#/components/schemas/Respuesta")), @OA\Response(response=401, description="Token inválido"), @OA\Response(response=403, description="Usuario sin rol admin"), @OA\Response(response=409, description="Referencia duplicada de otro cliente o transición inválida"), @OA\Response(response=422, description="Formulario inválido o cliente inexistente"))
     */
    public function acreditar(): void {}
}
