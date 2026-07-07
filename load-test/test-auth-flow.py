import requests

# Step 1: Login
login = requests.post(
    "http://localhost:8000/api/login",
    json={
        "email": "voidwalker1290@gmail.com",
        "password": "12345678",
    },
    headers={"Accept": "application/json"},
)
print("LOGIN Status:", login.status_code)
token = login.json().get("token")
print("Token:", token)

# Step 2: Pake token buat GET menus
menus = requests.get(
    "http://localhost:8000/api/menus",
    headers={
        "Authorization": f"Bearer {token}",
        "Accept": "application/json",
    },
)
print("\nGET /api/menus Status:", menus.status_code)
print("Response:", menus.text[:300])