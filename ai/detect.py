#!/usr/bin/env python3
import json
import sys
import warnings

import cv2
from inference import get_model


# Some environments emit third-party warnings to stderr and can pollute shell output.
warnings.filterwarnings("ignore")


def fail(message: str) -> None:
    print(json.dumps({"error": message}, ensure_ascii=False))
    sys.exit(1)


def main() -> None:
    if len(sys.argv) < 4:
        fail("Thieu tham so. Can: detect.py <image_path> <model_id> <api_key> [min_confidence]")

    image_path = sys.argv[1]
    model_id = sys.argv[2]
    api_key = sys.argv[3]
    min_confidence = 0.5

    if len(sys.argv) >= 5:
        try:
            min_confidence = float(sys.argv[4])
        except ValueError:
            min_confidence = 0.5

    image = cv2.imread(image_path)
    if image is None:
        fail("Khong doc duoc anh dau vao.")

    model = get_model(model_id=model_id, api_key=api_key)
    results = model.infer(image)

    predictions = results.get("predictions", []) if isinstance(results, dict) else []
    detections = []
    for pred in predictions:
        if not isinstance(pred, dict):
            continue

        confidence = float(pred.get("confidence", 0))
        if confidence < min_confidence:
            continue

        detections.append(
            {
                "label": str(pred.get("class", "")),
                "confidence": round(confidence, 4),
                "x": pred.get("x"),
                "y": pred.get("y"),
                "width": pred.get("width"),
                "height": pred.get("height"),
            }
        )

    payload = {
        "predictions": detections,
        "image": {
            "width": int(image.shape[1]),
            "height": int(image.shape[0]),
        },
    }
    print(json.dumps(payload, ensure_ascii=False))


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:  # Keep output JSON-only for PHP caller.
        fail(str(exc))
