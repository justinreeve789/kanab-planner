import sys
from sentence_transformers import SentenceTransformer, util

if len(sys.argv) != 3:
    print("Usage: python compare_phrases.py 'phrase one' 'phrase two'")
    sys.exit(1)

phrase1 = sys.argv[1]
phrase2 = sys.argv[2]

model = SentenceTransformer('all-mpnet-base-v2')

embeddings = model.encode([phrase1, phrase2], convert_to_tensor=True)
similarity = util.pytorch_cos_sim(embeddings[0], embeddings[1])

print(f"{similarity.item():.4f}")
