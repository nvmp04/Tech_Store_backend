<?php
class ProductsService {
  public function __construct(private PDO $pdo) {}

  public function getAll(): array {
    $sql = "
      SELECT
        id, name, price, old_price, badge,
        rating, reviews, in_stock, images,
        cpu, ram, storage, display, gpu, os, description,
        category,
        created_at, updated_at
      FROM products
      ORDER BY id ASC
    ";
    $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
      $r['images'] = is_string($r['images'])
        ? (json_decode($r['images'], true) ?: [])
        : ($r['images'] ?? []);

      $r['specs'] = (object)[
        'cpu'     => $r['cpu']     ?? null,
        'ram'     => $r['ram']     ?? null,
        'storage' => $r['storage'] ?? null,
        'display' => $r['display'] ?? null,
        'gpu'     => $r['gpu']     ?? null,
        'os'      => $r['os']      ?? null,
      ];

      if (isset($r['in_stock'])) $r['in_stock'] = (int)$r['in_stock'];
    }

    return $rows;
  }

  public function create(array $input): array {
    $name  = trim($input['name'] ?? '');
    $price = $input['price'] ?? null;
    if ($name === '' || !is_numeric($price)) {
      return ['success' => false, 'message' => 'name và price là bắt buộc'];
    }
    $old_price   = (int)($input['old_price'] ?? 0);
    $badge       = $input['badge'] ?? null;
    $rating      = (float)($input['rating'] ?? 0);
    $reviews     = (int)($input['reviews'] ?? 0);
    $in_stock    = isset($input['inStock']) ? (int)$input['inStock'] : 0;
    $description = $input['description'] ?? null;
    $category    = $input['category'] ?? null; 

    $images = $input['images'] ?? [];
    if (is_string($images)) $images = [$images];
    if (!is_array($images)) return ['success'=>false,'message'=>'images phải là string hoặc array các URL'];

    $images = array_values(array_filter(array_map(function($u){
      if (!is_string($u)) return null;
      $u = trim($u);
      return preg_match('#^https?://#i', $u) ? $u : null;
    }, $images)));
    $imagesJson = $images ? json_encode($images, JSON_UNESCAPED_UNICODE) : null;

    $specs = is_array($input['specs'] ?? null) ? $input['specs'] : [];
    $cpu     = $specs['cpu']     ?? ($input['cpu']     ?? null);
    $ram     = $specs['ram']     ?? ($input['ram']     ?? null);
    $storage = $specs['storage'] ?? ($input['storage'] ?? null);
    $display = $specs['display'] ?? ($input['display'] ?? null);
    $gpu     = $specs['gpu']     ?? ($input['gpu']     ?? null);
    $os      = $specs['os']      ?? ($input['os']      ?? null);

    try {
      $sql = "INSERT INTO products
              (name, price, old_price, badge, rating, reviews, in_stock, images,
               cpu, ram, storage, display, gpu, os, description, category)
              VALUES
              (:name, :price, :old_price, :badge, :rating, :reviews, :in_stock, :images,
               :cpu, :ram, :storage, :display, :gpu, :os, :description, :category)";
      $st = $this->pdo->prepare($sql);
      $st->execute([
        ':name' => $name,
        ':price' => (int)$price,
        ':old_price' => $old_price,
        ':badge' => $badge,
        ':rating' => $rating,
        ':reviews' => $reviews,
        ':in_stock' => $in_stock,
        ':images' => $imagesJson,
        ':cpu' => $cpu,
        ':ram' => $ram,
        ':storage' => $storage,
        ':display' => $display,
        ':gpu' => $gpu,
        ':os' => $os,
        ':description' => $description,
        ':category' => $category,
      ]);
      $id = (int)$this->pdo->lastInsertId();

      return ['success' => true, 'message' => 'Tạo sản phẩm thành công', 'id' => $id];

    } catch (\Throwable $e) {
      return ['success' => false, 'message' => 'Lỗi khi tạo sản phẩm', 'error' => $e->getMessage()];
    }
  }

  public function getOne($id): array {
    $sql = "
      SELECT *
      FROM products
      WHERE id = $id
      ORDER BY id ASC
    ";
    $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
      $r['images'] = is_string($r['images'])
        ? (json_decode($r['images'], true) ?: [])
        : ($r['images'] ?? []);

      $r['specs'] = (object)[
        'cpu'     => $r['cpu']     ?? null,
        'ram'     => $r['ram']     ?? null,
        'storage' => $r['storage'] ?? null,
        'display' => $r['display'] ?? null,
        'gpu'     => $r['gpu']     ?? null,
        'os'      => $r['os']      ?? null,
      ];

      if (isset($r['in_stock'])) $r['in_stock'] = (int)$r['in_stock'];
    }

    return $rows;
  }

  public function update(int $id, array $input): array {
    // Kiểm tra product tồn tại
    $sql = "SELECT id FROM products WHERE id = ?";
    $st = $this->pdo->prepare($sql);
    $st->execute([$id]);
    if (!$st->fetch()) {
      return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
    }

    $updates = [];
    $params = [];

    // Validate và chuẩn bị các field cần update
    if (isset($input['name'])) {
      $name = trim($input['name'] ?? '');
      if ($name === '') {
        return ['success' => false, 'message' => 'Tên sản phẩm không được để trống'];
      }
      $updates[] = "name = ?";
      $params[] = $name;
    }

    if (isset($input['price'])) {
      if (!is_numeric($input['price'])) {
        return ['success' => false, 'message' => 'Giá không hợp lệ'];
      }
      $updates[] = "price = ?";
      $params[] = (int)$input['price'];
    }

    if (isset($input['old_price'])) {
      $updates[] = "old_price = ?";
      $params[] = (int)($input['old_price'] ?? 0);
    }

    if (isset($input['badge'])) {
      $updates[] = "badge = ?";
      $params[] = $input['badge'] ?? null;
    }

    if (isset($input['rating'])) {
      $updates[] = "rating = ?";
      $params[] = (float)($input['rating'] ?? 0);
    }

    if (isset($input['reviews'])) {
      $updates[] = "reviews = ?";
      $params[] = (int)($input['reviews'] ?? 0);
    }

    if (isset($input['inStock'])) {
      $updates[] = "in_stock = ?";
      $params[] = (int)$input['inStock'];
    }

    if (isset($input['description'])) {
      $updates[] = "description = ?";
      $params[] = $input['description'] ?? null;
    }

    if (isset($input['category'])) {
      $updates[] = "category = ?";
      $params[] = $input['category'] ?? null;
    }

    // Xử lý images
    if (isset($input['images'])) {
      $images = $input['images'];
      if (is_string($images)) $images = [$images];
      if (!is_array($images)) {
        return ['success'=>false,'message'=>'images phải là string hoặc array các URL'];
      }

      $images = array_values(array_filter(array_map(function($u){
        if (!is_string($u)) return null;
        $u = trim($u);
        return preg_match('#^https?://#i', $u) ? $u : null;
      }, $images)));
      $imagesJson = $images ? json_encode($images, JSON_UNESCAPED_UNICODE) : null;
      
      $updates[] = "images = ?";
      $params[] = $imagesJson;
    }

    // Xử lý specs
    $specs = is_array($input['specs'] ?? null) ? $input['specs'] : [];
    
    if (isset($input['cpu']) || isset($specs['cpu'])) {
      $updates[] = "cpu = ?";
      $params[] = $specs['cpu'] ?? ($input['cpu'] ?? null);
    }

    if (isset($input['ram']) || isset($specs['ram'])) {
      $updates[] = "ram = ?";
      $params[] = $specs['ram'] ?? ($input['ram'] ?? null);
    }

    if (isset($input['storage']) || isset($specs['storage'])) {
      $updates[] = "storage = ?";
      $params[] = $specs['storage'] ?? ($input['storage'] ?? null);
    }

    if (isset($input['display']) || isset($specs['display'])) {
      $updates[] = "display = ?";
      $params[] = $specs['display'] ?? ($input['display'] ?? null);
    }

    if (isset($input['gpu']) || isset($specs['gpu'])) {
      $updates[] = "gpu = ?";
      $params[] = $specs['gpu'] ?? ($input['gpu'] ?? null);
    }

    if (isset($input['os']) || isset($specs['os'])) {
      $updates[] = "os = ?";
      $params[] = $specs['os'] ?? ($input['os'] ?? null);
    }

    if (empty($updates)) {
      return ['success' => false, 'message' => 'Không có dữ liệu cần cập nhật'];
    }

    $params[] = $id;
    $sql = "UPDATE products SET " . implode(", ", $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";

    try {
      $st = $this->pdo->prepare($sql);
      $st->execute($params);
      return ['success' => true, 'message' => 'Cập nhật sản phẩm thành công', 'id' => $id];
    } catch (\Throwable $e) {
      return ['success' => false, 'message' => 'Lỗi khi cập nhật sản phẩm', 'error' => $e->getMessage()];
    }
  }

  public function delete(int $id): array {
    // Kiểm tra product tồn tại
    $sql = "SELECT id FROM products WHERE id = ?";
    $st = $this->pdo->prepare($sql);
    $st->execute([$id]);
    if (!$st->fetch()) {
      return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
    }

    try {
      $sql = "DELETE FROM products WHERE id = ?";
      $st = $this->pdo->prepare($sql);
      $st->execute([$id]);
      return ['success' => true, 'message' => 'Xóa sản phẩm thành công', 'id' => $id];
    } catch (\Throwable $e) {
      return ['success' => false, 'message' => 'Lỗi khi xóa sản phẩm', 'error' => $e->getMessage()];
    }
  }
}
