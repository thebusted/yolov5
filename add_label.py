import numpy as np
import tensorflow as tf

# Load the TFLite model
interpreter = tf.lite.Interpreter(model_path="cattle-fp16.tflite")
interpreter.allocate_tensors()

# Get input and output tensors
input_details = interpreter.get_input_details()
output_details = interpreter.get_output_details()

# Load the labelmap.txt file
with open("labelmap.txt", "r") as f:
    labels = [line.strip() for line in f.readlines()]

# ... (code to run inference with your model) ...

# Get the raw output from the model
output_data = interpreter.get_tensor(output_details[0]['index'])

# Find the class with the highest confidence
top_prediction = np.argmax(output_data[0])

# Display the corresponding label
predicted_label = labels[top_prediction]
print(predicted_label) 