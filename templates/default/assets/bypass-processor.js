class BypassProcessor extends AudioWorkletProcessor {
    process (inputs, outputs) {
        console.log(inputs);
        // Single input, single channel.
        const input = inputs[0];
        const output = outputs[0];
        //output[0].set(input[0]);        
        // Process only while there are active inputs.
        return false;
    }
};

registerProcessor('bypass-processor', BypassProcessor);
