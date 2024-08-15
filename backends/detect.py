import platform
import torch

# Check if the OS is Windows and change the pathlib.PosixPath to pathlib.WindowsPath
if platform.system() == "Windows":
    import pathlib
    temp = pathlib.PosixPath
    pathlib.PosixPath = pathlib.WindowsPath

from pathlib import Path
from models.common import DetectMultiBackend
from utils.dataloaders import IMG_FORMATS, VID_FORMATS, LoadImages
from utils.general import (
    Profile,
    check_file,
    check_img_size,
    non_max_suppression,
    scale_boxes,
)
from utils.torch_utils import select_device
    
def detect(data):
    print('Detect data:', data)
    
    source = str(data['source'])
    is_file = Path(source).suffix[1:] in (IMG_FORMATS + VID_FORMATS)
    
    if is_file:
        source = check_file(source)  # download
        
    imgsz = (640, 640)
    
    weights = str(data['weights'])
    vid_stride = 1
    dnn = False
    half = False
    device = ""
    augment = False
    
    conf_thres = 0.25
    iou_thres = 0.45
    classes = None
    agnostic_nms = False
    max_det = 1000
    line_thickness = 3

    # Load model
    device = select_device(device)
    model = DetectMultiBackend(weights, device=device, dnn=dnn, data=data, fp16=half)
    stride, names, pt = model.stride, model.names, model.pt
    imgsz = check_img_size(imgsz, s=stride)  # check image size
        
    # Dataloader
    bs = 1  # batch_size
    dataset = LoadImages(source, img_size=imgsz, stride=stride, auto=pt, vid_stride=vid_stride)
    vid_path, vid_writer = [None] * bs, [None] * bs
    
    print('Dataset:', dataset)
    print('Source:', source)
    
    result = []
    
     # Run inference
    model.warmup(imgsz=(1 if pt or model.triton else bs, 3, *imgsz))  # warmup
    seen, windows, dt = 0, [], (Profile(device=device), Profile(device=device), Profile(device=device))
    for path, im, im0s, vid_cap, s in dataset:
        with dt[0]:
            im = torch.from_numpy(im).to(model.device)
            im = im.half() if model.fp16 else im.float()  # uint8 to fp16/32
            im /= 255  # 0 - 255 to 0.0 - 1.0
            if len(im.shape) == 3:
                im = im[None]  # expand for batch dim
            if model.xml and im.shape[0] > 1:
                ims = torch.chunk(im, im.shape[0], 0)
                
        # Inference
        with dt[1]:
            # visualize = increment_path(save_dir / Path(path).stem, mkdir=True) if visualize else False
            if model.xml and im.shape[0] > 1:
                print('if')
                # pred = None
                # for image in ims:
                #     if pred is None:
                #         pred = model(image, augment=augment, visualize=visualize).unsqueeze(0)
                #     else:
                #         pred = torch.cat((pred, model(image, augment=augment, visualize=visualize).unsqueeze(0)), dim=0)
                # pred = [pred, None]
            else:
                print('else')
                pred = model(im, augment=augment)
                
        # NMS
        with dt[2]:
            pred = non_max_suppression(pred, conf_thres, iou_thres, classes, agnostic_nms, max_det=max_det)
            
        print('Prediction:', pred)
        
        # Iterate through predictions
        for i, det in enumerate(pred):
            p, im0, frame = path, im0s.copy(), getattr(dataset, "frame", 0)
            
            print('Path:', path)
            print('p:', p)
            
            if len(det):
                # Rescale boxes from img_size to im0 size
                det[:, :4] = scale_boxes(im.shape[2:], det[:, :4], im0.shape).round()
                
                json_data = det.tolist()
                
                # Add image width and height into json_data array
                json_data = [json_data[i] + [im0.shape[1], im0.shape[0]] for i in range(len(json_data))]
                
                # Add label name into json_data array
                json_data = [json_data[i] + [names[int(json_data[i][5])]] for i in range(len(json_data))]
                
                # Put the data in the result array
                result.append({
                    "file": p,
                    "payload": json_data
                })
    
    print('Result:', result)
    return result