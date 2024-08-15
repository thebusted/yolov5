import ultralytics
import numpy as np
from ultralytics import YOLO
ultralytics.checks()

def segment(data):
    print('Detect data:', data)
    
    model = YOLO(data['weights'])
    
    # Run batched inference on a list of images
    results = model(
        data['source'],
        stream=True
    )
    
    predicts = []
    
    for result in results:
        img_path = result.path
        boxes = result.boxes
        masks = result.masks
        
        json_data = []
        if len(boxes) > 0:
            for mask, box in zip(result.masks.xy, result.boxes):
                points = np.int32([mask])
                
                x1, y1, x2, y2 = list(map(int, box.xyxy[0].cpu().numpy()))

                cls = int(box.cls[0])
                conf = float(box.conf[0])
                
                height, width = box.orig_shape
                
                # 6 decimal on conf
                conf = round(conf, 6)
                
                # Label
                label = model.names[cls]
                
                json_data.append([x1, y1, x2, y2, points, conf, cls, width, height, label])
            
        predicts.append({
            "file": img_path,
            "payload": json_data
        })
    
    return predicts

# Check and run main function
if __name__ == '__main__':
    # Test data
    data = {
        'weights': '/mnt/volume_sgp1_02/aiml/weights/segment/car-damage.pt',
        'source': '/mnt/volume_sgp1_02/aiml/public/car-damage/uploads/668f7b69d4152/deb9f9efc56ef2a940bdf0d58ccaad5c_XL.jpg'
    }
    
    # Run segment function
    result = segment(data)
    
    # Print result
    print(result)