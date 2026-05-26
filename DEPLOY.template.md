# Deploy a producción — [NOMBRE DEL PROYECTO]

> **Para Claude Code / IA:** Lee este archivo antes de cualquier deploy. Contiene las credenciales y el flujo exacto a seguir para este proyecto.

---

## Credenciales de producción

| Campo              | Valor                              |
|--------------------|------------------------------------|
| FTP Host           | `ftp.DOMINIO.com`                  |
| FTP User           | `usuario@DOMINIO.com`              |
| FTP Pass           | `CONTRASEÑA`                       |
| FTP Port           | `21`                               |
| Ruta remota raíz   | `/public_html/`                    |
| URL producción     | `https://DOMINIO.com`              |
| DB Host            | `localhost` (solo desde servidor)  |
| DB Port            | `3306`                             |
| DB Name            | `DBNAME_prod`                      |
| DB User            | `DBUSER`                           |
| DB Pass            | `CONTRASEÑA`                       |
| DB Charset         | `utf8mb4`                          |

---

## Regla crítica de conexión FTP

**Nunca usar `ftpes://`** — lftp 4.9.3 no lo soporta.
Usar siempre: `ftp://` + `set ftp:ssl-force yes`

---

## 1. Deploy de archivos PHP / JS / CSS

```bash
cat > /tmp/deploy.lftp << 'LFTP'
open -u usuario@DOMINIO.com,CONTRASEÑA ftp://ftp.DOMINIO.com
set ftp:ssl-force yes
set ssl:verify-certificate no

# — Agrega aquí los archivos modificados —
put /ruta/local/archivo.php -o /public_html/ruta/remota/archivo.php

bye
LFTP

lftp -f /tmp/deploy.lftp && echo "DEPLOY OK"
```

> **Tip:** lista todos los archivos cambiados con `git diff --name-only HEAD~1` y agrégalos al script.

---

## 2. Migraciones de base de datos

La DB de producción es `localhost` — no accesible por TCP externo.
Técnica: subir script PHP temporal → ejecutar vía HTTP → eliminar inmediatamente.

### Paso 1 — Crear el script migratorio en `/tmp/migrate_prod.php`

```php
<?php
$pdo = new PDO(
    'mysql:host=localhost;port=3306;dbname=DBNAME_prod;charset=utf8mb4',
    'DBUSER',
    'CONTRASEÑA',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sql = <<<SQL
-- Escribe aquí tus sentencias ALTER TABLE, CREATE TABLE, etc.
-- ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(20) NULL;
SQL;

$statements = array_filter(array_map('trim', explode(';', $sql)));
$results = [];
foreach ($statements as $stmt) {
    if ($stmt === '') continue;
    try {
        $pdo->exec($stmt);
        $results[] = ['ok' => true,  'stmt' => substr($stmt, 0, 100)];
    } catch (PDOException $e) {
        $results[] = ['ok' => false, 'stmt' => substr($stmt, 0, 100), 'error' => $e->getMessage()];
    }
}
header('Content-Type: application/json');
echo json_encode(['executed' => count($results), 'results' => $results], JSON_PRETTY_PRINT);
```

### Paso 2 — Subir, ejecutar y eliminar (hacerlo en secuencia inmediata)

```bash
# 1. Subir el script
lftp -u usuario@DOMINIO.com,CONTRASEÑA ftp://ftp.DOMINIO.com \
  -e "set ftp:ssl-force yes; set ssl:verify-certificate no; \
      put /tmp/migrate_prod.php -o /public_html/migrate_prod.php; bye"

# 2. Ejecutar
curl -s "https://DOMINIO.com/migrate_prod.php" | python3 -m json.tool

# 3. Eliminar INMEDIATAMENTE
lftp -u usuario@DOMINIO.com,CONTRASEÑA ftp://ftp.DOMINIO.com \
  -e "set ftp:ssl-force yes; set ssl:verify-certificate no; \
      rm /public_html/migrate_prod.php; bye"
```

> ⚠️ **Seguridad:** el script expone acceso a la BD mientras esté en el servidor. Nunca dejarlo más de 60 segundos ni overnight.

---

## 3. Verificar deploy

```bash
# Verificar que la URL responde 200
curl -o /dev/null -s -w "%{http_code}" https://DOMINIO.com

# Listar archivos en una ruta remota
lftp -u usuario@DOMINIO.com,CONTRASEÑA ftp://ftp.DOMINIO.com \
  -e "set ftp:ssl-force yes; set ssl:verify-certificate no; \
      ls /public_html/app/Views/; bye"
```

---

## 4. Checklist de deploy estándar

1. `git add` + `git commit` en rama `dev`
2. Escribir script lftp con todos los archivos modificados (`git diff --name-only HEAD~1`)
3. `lftp -f /tmp/deploy.lftp && echo "DEPLOY OK"`
4. Si hay cambios de schema: subir → ejecutar migración → eliminar script (< 60 seg)
5. Verificar URL de producción responde 200
6. `git push origin dev`

---

## 5. Estructura de carpetas en producción

```
/public_html/               ← raíz del proyecto
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Views/
├── config/
├── core/
├── database/
├── vendor/
└── index.php
```

> Ajusta esta sección según la estructura real del proyecto.
