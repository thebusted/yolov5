import onnx
import onnx_tf

# Load  ONNX model
onnx_model = onnx.load('cattle.onnx')

# Convert ONNX model to TensorFlow format
tf_model = onnx_tf.backend.prepare(onnx_model)

onnx.helper.printable_graph(tf_model.graph)
