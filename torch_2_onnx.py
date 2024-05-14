import torch
import onnx

# Load the YOLOv5 model (ensure you have PyTorch installed)
model = torch.hub.load(
    "ultralytics/yolov5", "yolov5s"
)  # Or your custom trained model path

# Example Input (replace with your actual image)
img = torch.zeros((1, 3, 640, 640))  # Sample image shape

# Inference (may be needed for some models)
model(img)

# Set the model to evaluation mode
model.eval()

# Export to ONNX format
torch.onnx.export(
    model,  # model being run
    img,  # model input (example)
    "yolov5.onnx",  # where to save the model (filename)
    export_params=True,  # store the trained parameter weights
    opset_version=12,  # the ONNX version to export
    do_constant_folding=True,  # whether to execute constant folding for optimization
    input_names=["input"],  # the model's input names
    output_names=["output"],  # the model's output names
)
