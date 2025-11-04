<?php
class ProductsService {
  public function __construct(private PDO $pdo) {}

  public function getAll(): array {
    $sql = "
      SELECT
        id, name, price, old_price, badge,
        rating, reviews, in_stock, images,
        cpu, ram, storage, display, gpu, os, description,
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

      if (isset($r['in_stock'])) $r['in_stock'] = (bool)$r['in_stock'];
    }

    return $rows;
  }

  public function create(array $input): array {
    $name  = trim($input['name'] ?? '');
    $price = $input['price'] ?? null;
    if ($name === '' || !is_numeric($price)) {
      return ['success' => false, 'message' => 'name và price là bắt buộc'];
    }
    $old_price  = (int)($input['old_price'] ?? 0);
    $badge      = $input['badge'] ?? null;
    $rating     = (float)($input['rating'] ?? 0);
    $reviews    = (int)($input['reviews'] ?? 0);
    $in_stock   = isset($input['inStock']) ? (int)!!$input['inStock'] : 1;
    $description= $input['description'] ?? null;

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
               cpu, ram, storage, display, gpu, os, description)
              VALUES
              (:name, :price, :old_price, :badge, :rating, :reviews, :in_stock, :images,
               :cpu, :ram, :storage, :display, :gpu, :os, :description)";
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
      ]);
      $id = (int)$this->pdo->lastInsertId();

      return ['success' => true, 'message' => 'Tạo sản phẩm thành công', 'id' => $id];

    } catch (\Throwable $e) {
      return ['success' => false, 'message' => 'Lỗi khi tạo sản phẩm', 'error' => $e->getMessage()];
    }
  }

  public function getOne($id): array {
    $sql = "
      SELECT * FROM products WHERE id = $id
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

      if (isset($r['in_stock'])) $r['in_stock'] = (bool)$r['in_stock'];
    }

    return $rows;
  }

}
