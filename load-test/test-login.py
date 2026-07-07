import requests

response = requests.post(
    "http://localhost:8000/api/login",
    json={
        "email": "voidwalker1290@gmail.com",
        "password": "12345678",
    },
    headers={"Accept": "application/json"},
)

print("Status:", response.status_code)
print("Response:", response.json())