CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token TEXT NOT NULL UNIQUE,
    request_key TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'new' CHECK(status IN ('new','contacted','processing','courier','ready','delivered','collected','cancelled')),
    receiving TEXT NOT NULL CHECK(receiving IN ('delivery','pickup')),
    customer TEXT NOT NULL CHECK(json_valid(customer)),
    tariff TEXT NOT NULL CHECK(json_valid(tariff)),
    subtotal INTEGER NOT NULL CHECK(subtotal >= 0),
    delivery_fee INTEGER NOT NULL CHECK(delivery_fee >= 0),
    total INTEGER NOT NULL CHECK(total >= 0),
    requested_at TEXT NOT NULL DEFAULT '',
    agreed_at TEXT NOT NULL DEFAULT '',
    next_contact TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    finished_at TEXT,
    anonymized INTEGER NOT NULL DEFAULT 0 CHECK(anonymized IN (0,1)),
    version INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id TEXT NOT NULL,
    name TEXT NOT NULL,
    portion TEXT NOT NULL,
    price INTEGER NOT NULL CHECK(price >= 0),
    quantity INTEGER NOT NULL CHECK(quantity BETWEEN 1 AND 99),
    comment TEXT NOT NULL DEFAULT ''
);
CREATE TABLE IF NOT EXISTS order_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    created_at TEXT NOT NULL,
    actor TEXT NOT NULL,
    message TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS order_mail_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    recipient_kind TEXT NOT NULL CHECK(recipient_kind IN ('manager','customer')),
    attempts INTEGER NOT NULL DEFAULT 0,
    next_attempt INTEGER NOT NULL DEFAULT 0,
    state TEXT NOT NULL DEFAULT 'pending' CHECK(state IN ('pending','sent','failed')),
    last_error TEXT NOT NULL DEFAULT '',
    UNIQUE(order_id, recipient_kind)
);
CREATE TABLE IF NOT EXISTS order_attempts (
    ip_hash TEXT PRIMARY KEY,
    started INTEGER NOT NULL,
    attempts INTEGER NOT NULL
);
