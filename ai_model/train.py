"""
Train the "Snap & Detect" CNN (SRS FR-AI.2, FR-AI.3, NFR-REL.3).

Dataset layout (500+ labelled images recommended, see SRS 2.5):

    dataset/
        Healthy/            *.jpg
        Green Mold/         *.jpg
        Bacterial Blotch/   *.jpg
        Pest Attack/        *.jpg

The folder names must match the disease names in the `disease_types` table.

Usage:
    pip install -r requirements.txt
    python train.py --data dataset --epochs 15

Outputs:
    mushroom_cnn.keras      trained model
    labels.json             class order used by app.py
    confusion_matrix.png    evaluation chart for the report
    evaluation.txt          accuracy / precision / recall per class
"""
import argparse
import json

import numpy as np
import tensorflow as tf
from sklearn.metrics import classification_report, confusion_matrix

IMG_SIZE = (224, 224)


def build_model(num_classes: int) -> tf.keras.Model:
    # Augmentation mirrors real farm photos: rotation, flips, zoom, low light.
    augment = tf.keras.Sequential([
        tf.keras.layers.RandomFlip("horizontal"),
        tf.keras.layers.RandomRotation(0.15),
        tf.keras.layers.RandomZoom(0.15),
        tf.keras.layers.RandomBrightness(0.25),
        tf.keras.layers.RandomContrast(0.2),
    ], name="augmentation")

    # Transfer learning on MobileNetV2 - accurate with a small dataset and
    # fast enough on a normal laptop/server (NFR-PERF.1).
    base = tf.keras.applications.MobileNetV2(input_shape=IMG_SIZE + (3,), include_top=False, weights="imagenet")
    base.trainable = False

    inputs = tf.keras.Input(shape=IMG_SIZE + (3,))
    x = augment(inputs)
    x = tf.keras.applications.mobilenet_v2.preprocess_input(x)  # normalisation
    x = base(x, training=False)
    x = tf.keras.layers.GlobalAveragePooling2D()(x)
    x = tf.keras.layers.Dropout(0.3)(x)
    outputs = tf.keras.layers.Dense(num_classes, activation="softmax")(x)
    model = tf.keras.Model(inputs, outputs)
    model.compile(optimizer=tf.keras.optimizers.Adam(1e-3),
                  loss="sparse_categorical_crossentropy", metrics=["accuracy"])
    return model, base


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--data", default="dataset")
    parser.add_argument("--epochs", type=int, default=15)
    parser.add_argument("--fine_tune_epochs", type=int, default=5)
    args = parser.parse_args()

    train_ds = tf.keras.utils.image_dataset_from_directory(
        args.data, validation_split=0.2, subset="training", seed=42, image_size=IMG_SIZE, batch_size=32)
    val_ds = tf.keras.utils.image_dataset_from_directory(
        args.data, validation_split=0.2, subset="validation", seed=42, image_size=IMG_SIZE, batch_size=32, shuffle=False)
    class_names = train_ds.class_names
    print("Classes:", class_names)

    model, base = build_model(len(class_names))
    callbacks = [tf.keras.callbacks.EarlyStopping(patience=4, restore_best_weights=True)]
    model.fit(train_ds, validation_data=val_ds, epochs=args.epochs, callbacks=callbacks)

    # Fine-tune the top of the base network for extra accuracy.
    base.trainable = True
    for layer in base.layers[:-30]:
        layer.trainable = False
    model.compile(optimizer=tf.keras.optimizers.Adam(1e-5),
                  loss="sparse_categorical_crossentropy", metrics=["accuracy"])
    model.fit(train_ds, validation_data=val_ds, epochs=args.fine_tune_epochs, callbacks=callbacks)

    # Evaluation with a confusion matrix (NFR-REL.3: target >= 80% accuracy).
    y_true = np.concatenate([y.numpy() for _, y in val_ds])
    y_pred = np.argmax(model.predict(val_ds), axis=1)
    report = classification_report(y_true, y_pred, target_names=class_names, digits=3)
    cm = confusion_matrix(y_true, y_pred)
    accuracy = float((y_true == y_pred).mean())
    print(report)
    print("Confusion matrix:\n", cm)
    print(f"Overall accuracy: {accuracy:.2%} (target >= 80%)")

    with open("evaluation.txt", "w") as f:
        f.write(report + "\nConfusion matrix:\n" + str(cm) + f"\n\nOverall accuracy: {accuracy:.2%}\n")

    try:
        import matplotlib
        matplotlib.use("Agg")
        import matplotlib.pyplot as plt
        fig, ax = plt.subplots(figsize=(6, 5))
        ax.imshow(cm, cmap="Greens")
        ax.set_xticks(range(len(class_names)), class_names, rotation=30, ha="right")
        ax.set_yticks(range(len(class_names)), class_names)
        for i in range(len(class_names)):
            for j in range(len(class_names)):
                ax.text(j, i, cm[i, j], ha="center", va="center")
        ax.set_xlabel("Predicted")
        ax.set_ylabel("Actual")
        ax.set_title(f"Snap & Detect CNN - accuracy {accuracy:.1%}")
        fig.tight_layout()
        fig.savefig("confusion_matrix.png", dpi=150)
    except ImportError:
        pass

    model.save("mushroom_cnn.keras")
    with open("labels.json", "w") as f:
        json.dump(class_names, f)
    print("Saved mushroom_cnn.keras and labels.json")


if __name__ == "__main__":
    main()
