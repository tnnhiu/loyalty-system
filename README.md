# Loyalty System - Full API Documentation

Tai lieu nay mo ta day du cac API hien co trong project theo dung code hien tai.

## 1) Run project

Run MySQL:

```bash
docker compose up -d
```

Run Symfony:

```bash
symfony serve -d
# hoac
php -S 127.0.0.1:8000 -t public
```

Mac dinh app chay tai: http://127.0.0.1:8000

## 2) Danh sach API hien co

1. GET /db-check
2. POST /api/members
3. POST /api/transactions/earn-points
4. POST /api/gifts
5. POST /api/redemptions
6. GET /api/members/{member_id}/wallet

## 3) API chi tiet

### 3.1 GET /db-check

Muc dich:

- Kiem tra ket noi MySQL tu app

Response 200:

```json
{
	"ok": true,
	"message": "Connected to MySQL successfully"
}
```

Response 500 (vi du):

```json
{
	"ok": false,
	"message": "Missing required environment variables",
	"missing": ["DB_HOST", "DB_PORT"]
}
```

### 3.2 POST /api/members

Muc dich:

- Tao member moi
- Tao wallet voi so du ban dau 0.00

Request body:

```json
{
	"full_name": "Nguyen Van A",
	"email": "a@gmail.com"
}
```

Validate:

- full_name: bat buoc, string, khong rong
- email: bat buoc, dung format email

Response 201:

```json
{
	"ok": true,
	"message": "Create member success.",
	"data": {
		"member": {
			"id": 1,
			"full_name": "Nguyen Van A",
			"email": "a@gmail.com",
			"created_at": "2026-04-20 10:00:00"
		},
		"wallet": {
			"id": 1,
			"balance": "0.00"
		}
	}
}
```

Response 400 (vi du):

```json
{
	"ok": false,
	"message": "email format is invalid."
}
```

Response 409 (vi du):

```json
{
	"ok": false,
	"message": "Member email already exists."
}
```

### 3.3 POST /api/transactions/earn-points

Muc dich (API 1):

- Tiep nhan giao dich mua hang cua member
- Luu transaction
- Cong diem vao wallet
- Ghi lich su point

Nghiep vu chinh:

- earned_points = amount x 1%
- amount la decimal toi da 2 chu so sau dau phay
- member phai ton tai

Request body:

```json
{
	"member_id": 1,
	"amount": 150000.00,
	"description": "Thanh toan don hang #1001"
}
```

Validate:

- member_id: so nguyen duong
- amount: decimal > 0, toi da 2 chu so thap phan
- description: string hoac null

Response 201:

```json
{
	"ok": true,
	"message": "Earn points success.",
	"data": {
		"transaction_id": 1,
		"member_id": 1,
		"amount": "150000.00",
		"earned_points": 1500,
		"wallet_balance": "1500.00",
		"status": "SUCCESS",
		"created_at": "2026-04-20 10:00:00"
	}
}
```

Response 404 (vi du):

```json
{
	"ok": false,
	"message": "Member not found."
}
```

### 3.4 POST /api/gifts

Muc dich:

- Tao gift de doi diem

Request body:

```json
{
	"gift_name": "Voucher 50k",
	"point_cost": 500,
	"stock": 100,
	"status": "ACTIVE"
}
```

Validate:

- gift_name: bat buoc, string, khong rong
- point_cost: integer > 0
- stock: integer >= 0
- status: ACTIVE hoac INACTIVE

Response 201:

```json
{
	"ok": true,
	"message": "Create gift success.",
	"data": {
		"id": 1,
		"gift_name": "Voucher 50k",
		"point_cost": 500,
		"stock": 100,
		"status": "ACTIVE"
	}
}
```

Response 400 (vi du):

```json
{
	"ok": false,
	"message": "status must be ACTIVE or INACTIVE."
}
```

### 3.5 POST /api/redemptions

Muc dich (API 2):

- Member dung diem de doi gift

Nghiep vu chinh:

- Gift phai ton tai, ACTIVE, con stock
- Wallet member phai ton tai va du diem
- Sau khi doi qua:
	- Tao redemption
	- Tru stock gift di 1
	- Tru diem wallet theo point_cost
	- Ghi point history am (so diem tru)

Request body:

```json
{
	"member_id": 1,
	"gift_id": 1
}
```

Validate:

- member_id: so nguyen duong
- gift_id: so nguyen duong

Response 201:

```json
{
	"ok": true,
	"message": "Redeem gift success.",
	"data": {
		"redemption_id": 1,
		"member_id": 1,
		"gift_id": 1,
		"gift_name": "Voucher 50k",
		"points_used": 500,
		"wallet_balance": "1000.00",
		"status": "COMPLETED",
		"created_at": "2026-04-20 10:30:00"
	}
}
```

Response 409 (vi du):

```json
{
	"ok": false,
	"message": "Insufficient points in wallet."
}
```

### 3.6 GET /api/members/{member_id}/wallet

Muc dich (API 3):

- Lay thong tin wallet cua member
- Lay lich su diem moi nhat (toi da 10 dong)

Path param:

- member_id: so nguyen duong

Response 200:

```json
{
	"ok": true,
	"message": "Get member point history success.",
	"data": {
		"member_id": 1,
		"wallet_id": 1,
		"wallet_balance": "1200.00",
		"total_items": 2,
		"items": [
			{
				"point_id": 10,
				"point_amount": 1500,
				"description": "Thanh toan don hang #1001",
				"transaction_id": 5,
				"transaction_amount": "150000.00",
				"redemption_id": null,
				"gift_id": null,
				"gift_name": null,
				"created_at": "2026-04-20 10:00:00"
			},
			{
				"point_id": 11,
				"point_amount": -300,
				"description": "Redeem gift: The cao 100k",
				"transaction_id": null,
				"transaction_amount": null,
				"redemption_id": 3,
				"gift_id": 2,
				"gift_name": "The cao 100k",
				"created_at": "2026-04-20 10:30:00"
			}
		]
	}
}
```

Response 404 (vi du):

```json
{
	"ok": false,
	"message": "Wallet not found."
}
```

## 4) Curl test nhanh

### Tao member

```bash
curl -X POST http://127.0.0.1:8000/api/members \
	-H "Content-Type: application/json" \
	-d '{"full_name":"Nguyen Van A","email":"a@gmail.com"}'
```

### Earn points

```bash
curl -X POST http://127.0.0.1:8000/api/transactions/earn-points \
	-H "Content-Type: application/json" \
	-d '{"member_id":1,"amount":150000.00,"description":"Thanh toan don hang #1001"}'
```

### Tao gift

```bash
curl -X POST http://127.0.0.1:8000/api/gifts \
	-H "Content-Type: application/json" \
	-d '{"gift_name":"Voucher 50k","point_cost":500,"stock":100,"status":"ACTIVE"}'
```

### Redeem gift

```bash
curl -X POST http://127.0.0.1:8000/api/redemptions \
	-H "Content-Type: application/json" \
	-d '{"member_id":1,"gift_id":1}'
```

### Xem wallet + history

```bash
curl http://127.0.0.1:8000/api/members/1/wallet
```

## 5) Luu y

- Toan bo ket noi DB chi lay tu bien moi truong: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD.
- API khong tu tao bang. Can co schema DB san.
- API topup da bi xoa va khong con trong project.
