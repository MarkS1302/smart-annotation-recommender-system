<script setup lang="ts">
import { computed, ref } from 'vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator
} from '@/components/ui/command';
import { ChevronDown, X, XCircle } from 'lucide-vue-next';
import { find, get, groupBy, isEmpty } from 'lodash';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';

const {
    options,
    placeholder = '',
    modalPopover = false,
    className = '',
    labelKey,
    valueKey,
    groupKey = null,
    alignment = 'start'
} = defineProps<{
    options: object[],
    placeholder?: string,
    modalPopover?: boolean,
    className?: string,
    labelKey: string,
    valueKey: string,
    groupKey?: string,
    alignment?: 'start' | 'center' | 'end',
    popoverWidth?: string
}>();

const model = defineModel();

const isPopoverOpen = ref(false);
const isAnimating = ref(false);

const groupedItems = computed(() => {
    return groupBy(options, 'parent_id');
});

const toggleOption = (option) => {
    if (!Array.isArray(model.value)) {
        model.value = [];
    }

    if (model.value.includes(option)) {
        model.value = model.value.filter(value => value !== option);
    } else {
        model.value.push(option);
    }
};


const clearAll = () => {
    model.value = [];
};

const handleInputKeyDown = (event) => {
    if (event.key === 'Enter') {
        isPopoverOpen.value = true;
    } else if (event.key === 'Backspace' && !event.currentTarget.value) {
        const newSelectedValues = model.value;
        newSelectedValues.pop();
        model.value = newSelectedValues;
    }
};
</script>

<template>

    <Popover v-model:open="isPopoverOpen" :modal="modalPopover">
        <PopoverTrigger class="w-full">
            <Button type="button" variant="outline"
                    :class="['flex w-full p-1 rounded-md border min-h-10 h-auto items-center justify-between bg-inherit hover:bg-inherit [&_svg]:pointer-events-auto', className, model?.length > 0 ? 'h-auto' : '']">
                <div v-if="model.length > 0" class="flex justify-between items-center w-full">
                    <div class="flex flex-wrap items-center">
                        <Badge v-for="value in model" :key="value"
                               :class="['m-1 px-3 py-1 transition ease-in-out text-xs', isAnimating ? 'animate-bounce' : '']">
                            {{ get(options.find(o => o[valueKey] === value), labelKey) }}
                            <button @click.stop="toggleOption(value)">
                                <XCircle class="ml-2 h-4 w-4 cursor-pointer" />
                            </button>
                        </Badge>
                    </div>
                    <div class="flex items-center">
                        <button @click.stop.prevent="clearAll">
                            <X class="h-4 mx-2 cursor-pointer text-muted-foreground" />
                        </button>
                        <Separator orientation="vertical" class="flex min-h-6 h-full" />
                        <ChevronDown class="h-4 mx-2 cursor-pointer text-muted-foreground" />
                    </div>
                </div>
                <div v-else class="flex items-center justify-between w-full mx-auto">
                    <span class="text-sm text-muted-foreground mx-3">{{ placeholder }}</span>
                    <ChevronDown class="h-4 cursor-pointer text-muted-foreground mx-2" />
                </div>
            </Button>
        </PopoverTrigger>
        <PopoverContent :class="cn('w-auto p-0',popoverWidth)" :align="alignment">
            <Command>
                <CommandInput @keydown="handleInputKeyDown" placeholder="Search..." />
                <CommandList>
                    <CommandEmpty> No Results
                    </CommandEmpty>
                    <CommandGroup v-if="isEmpty(groupKey)">
                        <CommandItem :value="option[valueKey]" v-for="option in options"
                                     :key="option[valueKey]"
                                     @select="toggleOption(option[valueKey])">
                            <Checkbox :modelValue="model.includes(option[valueKey])" class="h-4 w-4 mr-2" />
                            {{ option[labelKey] }}
                        </CommandItem>
                    </CommandGroup>
                    <div v-else class="flex flex-col">
                        <CommandGroup :key="parentId" v-for="(children, parentId) in groupedItems">
                            <CommandItem v-if="parentId !== 'null'" :value="parentId"
                                         :key="parentId"
                                         @select="toggleOption(parseInt(parentId))">
                                <Checkbox :modelValue="model.includes(parseInt(parentId))" class="h-4 w-4 mr-2" />
                                {{ get(find(options, (item) => item.id === parseInt(parentId)), labelKey) }}
                            </CommandItem>
                            <CommandItem :class="{'ml-4': parentId !== 'null'}" :value="option[valueKey]"
                                         v-for="option in children"
                                         :key="option[valueKey]"
                                         @select="toggleOption(option[valueKey])">
                                <Checkbox :modelValue="model.includes(option[valueKey])" class="h-4 w-4 mr-2" />
                                {{ option[labelKey] }}
                            </CommandItem>
                        </CommandGroup>

                    </div>
                    <CommandSeparator />
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>
