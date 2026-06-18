<?php
// Demo data used when database is not available

$DEMO_BRANDS = [
    ['id' => 1, 'name' => 'Pizza Hut', 'color' => '#E8001C', 'icon' => '🍕'],
    ['id' => 2, 'name' => 'McDonald\'s', 'color' => '#FFC72C', 'icon' => 'M'],
    ['id' => 3, 'name' => 'KFC', 'color' => '#E4002B', 'icon' => '🍗'],
    ['id' => 4, 'name' => 'Burger King', 'color' => '#FF8800', 'icon' => '🍔'],
    ['id' => 5, 'name' => 'Subway', 'color' => '#009639', 'icon' => '🥪'],
    ['id' => 6, 'name' => 'Domino\'s', 'color' => '#006491', 'icon' => '🍕'],
];

$DEMO_CATEGORIES = [
    ['id' => 1, 'name' => 'Burgers', 'icon' => '🍔'],
    ['id' => 2, 'name' => 'Pizza', 'icon' => '🍕'],
    ['id' => 3, 'name' => 'Chicken', 'icon' => '🍗'],
    ['id' => 4, 'name' => 'Sandwiches', 'icon' => '🥪'],
    ['id' => 5, 'name' => 'Desserts', 'icon' => '🍰'],
    ['id' => 6, 'name' => 'Drinks', 'icon' => '🥤'],
    ['id' => 7, 'name' => 'Sides', 'icon' => '🍟'],
    ['id' => 8, 'name' => 'Salads', 'icon' => '🥗'],
];

function buildProducts() {
    $items = [
        ['name'=>'Juicy Beef Patties','base_price'=>3908,'category'=>1,'brand'=>4,'discount'=>10,'is_new'=>false,'delivery'=>'free','img'=>'burger'],
        ['name'=>'Classic Double Burger','base_price'=>3880,'category'=>1,'brand'=>2,'discount'=>10,'is_new'=>false,'delivery'=>'paid','img'=>'burger'],
        ['name'=>'Veggie Supreme','base_price'=>3100,'category'=>1,'brand'=>2,'discount'=>0,'is_new'=>false,'delivery'=>'free','img'=>'burger'],
        ['name'=>'Crispy Chicken Burger','base_price'=>3908,'category'=>3,'brand'=>3,'discount'=>0,'is_new'=>true,'delivery'=>'free','img'=>'burger'],
        ['name'=>'Pepperoni Pizza','base_price'=>4200,'category'=>2,'brand'=>1,'discount'=>10,'is_new'=>false,'delivery'=>'free','img'=>'pizza'],
        ['name'=>'Margherita Pizza','base_price'=>3800,'category'=>2,'brand'=>1,'discount'=>15,'is_new'=>false,'delivery'=>'paid','img'=>'pizza'],
        ['name'=>'BBQ Chicken Pizza','base_price'=>4500,'category'=>2,'brand'=>6,'discount'=>0,'is_new'=>false,'delivery'=>'free','img'=>'pizza'],
        ['name'=>'Cheese Burst Pizza','base_price'=>4900,'category'=>2,'brand'=>1,'discount'=>0,'is_new'=>true,'delivery'=>'free','img'=>'pizza'],
        ['name'=>'Spicy Zinger','base_price'=>2800,'category'=>3,'brand'=>3,'discount'=>10,'is_new'=>false,'delivery'=>'free','img'=>'burger'],
        ['name'=>'Tower Burger','base_price'=>3200,'category'=>1,'brand'=>3,'discount'=>10,'is_new'=>false,'delivery'=>'paid','img'=>'burger'],
        ['name'=>'Whopper Meal','base_price'=>4100,'category'=>1,'brand'=>4,'discount'=>0,'is_new'=>false,'delivery'=>'free','img'=>'burger'],
        ['name'=>'BK Double Stack','base_price'=>3600,'category'=>1,'brand'=>4,'discount'=>0,'is_new'=>true,'delivery'=>'free','img'=>'burger'],
        ['name'=>'Meatball Sub','base_price'=>2500,'category'=>4,'brand'=>5,'discount'=>10,'is_new'=>false,'delivery'=>'free','img'=>'burger'],
        ['name'=>'Italian BMT','base_price'=>2700,'category'=>4,'brand'=>5,'discount'=>10,'is_new'=>false,'delivery'=>'paid','img'=>'burger'],
        ['name'=>'Tuna Classic','base_price'=>2300,'category'=>4,'brand'=>5,'discount'=>0,'is_new'=>false,'delivery'=>'free','img'=>'burger'],
        ['name'=>'Veggie Delite','base_price'=>2100,'category'=>4,'brand'=>5,'discount'=>0,'is_new'=>true,'delivery'=>'free','img'=>'burger'],
    ];
    $products = [];
    foreach ($items as $i => $item) {
        $op = $item['discount'] > 0 ? round($item['base_price'] / (1 - $item['discount']/100)) : null;
        $products[] = [
            'id' => $i + 1,
            'name' => $item['name'],
            'price' => $item['base_price'],
            'original_price' => $op,
            'discount_percent' => $item['discount'],
            'is_new' => $item['is_new'],
            'brand_id' => $item['brand'],
            'category_id' => $item['category'],
            'delivery_type' => $item['delivery'],
            'image' => $item['img'],
            'rating' => round(3.5 + mt_rand(0,15)/10, 1),
        ];
    }
    return $products;
}

$DEMO_PRODUCTS = buildProducts();
