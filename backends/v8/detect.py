import ultralytics
from ultralytics import YOLO
ultralytics.checks()

def detect(data):
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
        
        
        json_data = []
        for box in boxes:
            x1, y1, x2, y2 = box.xyxy[0].cpu().numpy()
            x1, y1, x2, y2 = int(x1), int(y1), int(x2), int(y2)

            cls = int(box.cls[0])
            conf = float(box.conf[0])
            
            height, width = box.orig_shape
            
            # 6 decimal on conf
            conf = round(conf, 6)
            
            # Label
            label = model.names[cls]
            
            json_data.append([x1, y1, x2, y2, conf, cls, width, height, label])
            

        predicts.append({
            "file": img_path,
            "payload": json_data
        })
    
    return predicts
