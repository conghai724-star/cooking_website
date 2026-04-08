# Quickstart Inference -> Goi Y Cong Thuc

Endpoint moi:
- `GET /ml/suggest-recipes` (de test nhanh, khong can CSRF)
- `POST /ml/suggest-recipes` (de noi tu frontend sau nay)

## Test nhanh voi GET
```text
http://localhost/cooking_website/ml/suggest-recipes?ingredients=ca%20chua,trung%20ga,hanh%20tay&limit=5
```

## Payload cho POST
1. Cach don gian:
```json
{
  "ingredients": ["ca chua", "trung ga", "hanh tay"],
  "limit": 6,
  "max_calories": 700,
  "keyword": "xao"
}
```

2. Cach theo output detector:
```json
{
  "detections": [
    {"label": "ca chua", "confidence": 0.91},
    {"label": "trung ga", "confidence": 0.88},
    {"label": "hanh tay", "confidence": 0.42}
  ],
  "limit": 6
}
```

## Response mau
```json
{
  "success": true,
  "message": "Goi y cong thuc thanh cong.",
  "data": {
    "labels": ["ca chua", "trung ga"],
    "resolved_ingredients": [
      {"id": 12, "name": "Ca chua"},
      {"id": 20, "name": "Trung ga"}
    ],
    "recipes": [
      {
        "id": 101,
        "title": "Trung xao ca chua",
        "url": "/recipes/101",
        "matched_count": 2
      }
    ]
  }
}
```

## Test nhanh bang PowerShell (GET)
```powershell
Invoke-RestMethod -Method Get -Uri 'http://localhost/cooking_website/ml/suggest-recipes?ingredients=ca%20chua,trung%20ga,hanh%20tay&limit=5'
```

## Ghi chu
- Endpoint nay chua goi model AI truc tiep.
- Ban co the noi detector sau: detector tra ve labels, sau do goi endpoint nay de lay cong thuc.
- Alias nguyen lieu dung bang `ingredient_aliases` + bang `ingredients` hien co.
- Neu dung `POST` trong web app, nho gui CSRF token dung theo luong hien tai cua du an.
