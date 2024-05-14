import yaml

data = {
    'path': '/content/dataset',
    'train': 'train',
    'val': 'valid',
    'test': 'test',
    'nc': 5,
    'names': [
        'Infected Foot',
        'Mouth Disease Infected',
        'Healthy',
        'Normal Mouth',
        'Lumpy Skin'
    ]
}

with open('data.yaml', 'w') as file:
     yaml.dump(data, file, default_flow_style=False)