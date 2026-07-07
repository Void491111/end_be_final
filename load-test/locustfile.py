import random
import itertools
from locust import HttpUser, task, between

CASHIER_ACCOUNTS = [
    {"email": "voidwalker1290@gmail.com", "password": "12345678"},
    {"email": "kasir1@test.com", "password": "password"},
    {"email": "kasir2@test.com", "password": "password"},
    {"email": "kasir3@test.com", "password": "password"},
    {"email": "kasir4@test.com", "password": "password"},
    {"email": "kasir5@test.com", "password": "password"},
    {"email": "kasir6@test.com", "password": "password"},
    {"email": "kasir7@test.com", "password": "password"},
    {"email": "kasir8@test.com", "password": "password"},
    {"email": "kasir9@test.com", "password": "password"},
]

# Iterator siklik — tiap user Locust dapet akun beda, unique
account_pool = itertools.cycle(CASHIER_ACCOUNTS)


class MooisteCafeUser(HttpUser):
    host = "http://localhost:8000"
    wait_time = between(1, 3)

    def on_start(self):
        self.token = None
        self.menu_ids = []
        self.auth_headers = {"Accept": "application/json"}

        # Assign akun UNIQUE via cycle (bukan random)
        account = next(account_pool)

        response = self.client.post(
            "/api/login",
            json=account,
            headers={"Accept": "application/json"},
            name="POST /api/login",
        )

        if response.status_code == 200:
            data = response.json()
            self.token = data.get("token")
            if self.token:
                self.client.cookies.clear()
                self.auth_headers = {
                    "Authorization": f"Bearer {self.token}",
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                }
        else:
            print(f"❌ Login gagal ({account['email']}): {response.status_code}")

    @task(5)
    def browse_menus(self):
        if not self.token:
            return
        response = self.client.get(
            "/api/menus",
            headers=self.auth_headers,
            name="GET /api/menus",
        )
        if response.status_code == 200:
            data = response.json()
            items = data.get("data", data) if isinstance(data, dict) else data
            if isinstance(items, list) and items:
                self.menu_ids = [item["id"] for item in items if "id" in item]

    @task(3)
    def create_order(self):
        if not self.token or not self.menu_ids:
            return

        picked = random.sample(
            self.menu_ids,
            k=min(random.randint(1, 3), len(self.menu_ids))
        )

        payload = {
            "order_type": random.choice(["dine_in", "takeaway"]),
            "items": [
                {"menu_id": mid, "quantity": random.randint(1, 3)}
                for mid in picked
            ],
        }

        self.client.post(
            "/api/orders",
            json=payload,
            headers=self.auth_headers,
            name="POST /api/orders",
        )

    @task(2)
    def list_orders(self):
        if not self.token:
            return
        self.client.get(
            "/api/orders?period=today",
            headers=self.auth_headers,
            name="GET /api/orders",
        )