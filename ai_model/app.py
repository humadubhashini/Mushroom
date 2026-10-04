"""
CNN inference API for "Snap & Detect" (SRS 4.3 CNN Inference API).

    pip install -r requirements.txt
    python app.py            # serves http://127.0.0.1:5000/predict

Then set AI_API_URL in config/app.php to 'http://127.0.0.1:5000/predict'.

POST /predict  (multipart field "image")  ->
    {"disease": "Green Mold", "confidence": 91.4, "model": "mushroom_cnn", "scores": {...}}
"""
import io
import json
import os

import numpy as np
from flask import Flask, jsonify, request
from PIL import Image, ImageOps

import tensorflow as tf

HERE = os.path.dirname(os.path.abspath(__file__))
IMG_SIZE = (224, 224)

model = tf.keras.models.load_model(os.path.join(HERE, "mushroom_cnn.keras"))
with open(os.path.join(HERE, "labels.json")) as f:
    labels = json.load(f)

app = Flask(__name__)


def preprocess(raw: bytes) -> np.ndarray:
    """Resize to the training size (FR-AI.2). Normalisation is inside the model."""
    img = Image.open(io.BytesIO(raw))
    img = ImageOps.exif_transpose(img).convert("RGB").resize(IMG_SIZE)
    return np.expand_dims(np.asarray(img, dtype=np.float32), 0)


@app.post("/predict")
def predict():
    file = request.files.get("image")
    if not file:
        return jsonify(error="No image uploaded"), 400
    try:
        batch = preprocess(file.read())
    except Exception:
        return jsonify(error="Invalid image"), 400

    probs = model.predict(batch, verbose=0)[0]
    best = int(np.argmax(probs))
    return jsonify(
        disease=labels[best],
        confidence=round(float(probs[best]) * 100, 1),
        model="mushroom_cnn",
        scores={label: round(float(p) * 100, 1) for label, p in zip(labels, probs)},
    )


@app.get("/health")
def health():
    return jsonify(status="ok", classes=labels)


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000)
