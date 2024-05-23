import platform
import time
import os

from backends.classify import matching, classify, training

# Check if the OS is Windows and change the pathlib.PosixPath to pathlib.WindowsPath
if platform.system() == "Windows":
    import pathlib
    temp = pathlib.PosixPath
    pathlib.PosixPath = pathlib.WindowsPath

from pathlib import Path
from backends.detect import detect

from fastapi import FastAPI, Query

app = FastAPI()

@app.get("/detect/{model}/{task_id}")
def run_detect(model: str, task_id: str, bucket: str = Query(None)):
    # Start time
    start_time = time.time()
    
    # Weight path merge with current_dir
    weight_path = Path(__file__).resolve().parent.joinpath('weights', 'detect', f"{model}.pt")
    
    print("bucket:", bucket)
    
    # Set result to None
    result = None
    
    # Check bucket is not None and is directory
    if bucket is not None and os.path.isdir(bucket):
        result = detect({
            "source": bucket,
            "weights": weight_path
        })
    
    # End time
    end_time = time.time()
    
    # Process time in milliseconds
    process_time = (end_time - start_time) * 1000
    return {
        "task_id": task_id,
        "result": result,
        "inference": process_time
    }
    
@app.get("/identify/{uid}/{task_id}")
def run_identify(uid: str, task_id: str, file: str = Query(None)):
    # Start time
    start_time = time.time()
    
    # Model path
    model_path = Path(__file__).resolve().parent.joinpath('weights', 'identify', f"{uid}")
    
    print("file:", file)
    
    # Set result to None
    result = None
    
    # Check bucket is not None and is directory
    if file is not None and os.path.isfile(file):
        result = matching({
            "resize": 240,
            "file": file,
            "model": model_path
        })
    
    # End time
    end_time = time.time()
    
    # Process time in milliseconds
    process_time = (end_time - start_time) * 1000
    return {
        "task_id": task_id,
        "result": result,
        "inference": process_time
    }
    
@app.get("/register/{uid}/{task_id}")
def run_register(uid: str, task_id: str, file: str = Query(None), cid: str = Query(None)):
    # Start time
    start_time = time.time()
    
    print("file:", file)
    
    # Set result to None
    result = None
    
    # Model path
    store = Path(__file__).resolve().parent.joinpath('weights', 'identify', f"{uid}")
    
    # Check bucket is not None and is directory
    if file is not None and os.path.isfile(file):
        result = training({
            "resize": 240,
            "file": file,
            "cid": cid,
            "uid": uid,
            "store": store
        })
    
    # End time
    end_time = time.time()
    
    # Process time in milliseconds
    process_time = (end_time - start_time) * 1000
    return {
        "task_id": task_id,
        "result": result,
        "inference": process_time
    }
    
@app.get("/classify/{type}/{task_id}")
def run_classify(type: str, task_id: str, file: str = Query(None)):
    # Start time
    start_time = time.time()
    
    # Model path
    model_path = Path(__file__).resolve().parent.joinpath('weights', 'classify', f"{type}.keras")
    
    print("file:", file)
    
    # Set result to None
    result = None
    
    # Check bucket is not None and is directory
    if file is not None and os.path.isfile(file):
        result = classify({
            "file": file,
            "model": model_path
        })
    
    # End time
    end_time = time.time()
    
    # Process time in milliseconds
    process_time = (end_time - start_time) * 1000
    return {
        "task_id": task_id,
        "result": result,
        "inference": process_time
    }
    
